<?php

namespace App\Console\Commands;

use App\Jobs\SyncTelegramGroup;
use App\Models\TelegramGroup;
use App\Services\IngestMessageService;
use App\Services\MessageParserService;
use App\Services\TelegramClientService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TelegramSync extends Command
{
    protected $signature = 'telegram:sync 
                            {--group= : Sync specific group by ID or title} 
                            {--limit=100 : Number of messages to fetch per group} 
                            {--full : Recursively crawl full channel history} 
                            {--queue : Dispatch as background queue jobs instead of running inline}';

    protected $description = 'Sync Telegram messages and ingest products from monitored groups';

    public function handle(
        TelegramClientService $telegramClient,
        MessageParserService $parser,
        IngestMessageService $ingestor
    ): int {
        $this->info('═══════════════════════════════════════════');
        $this->info('   Telegram Price Monitor — Group Sync');
        $this->info('═══════════════════════════════════════════');

        $query = TelegramGroup::query();

        if ($groupFilter = $this->option('group')) {
            if (is_numeric($groupFilter)) {
                $query->where('id', (int) $groupFilter)->orWhere('telegram_id', $groupFilter);
            } else {
                $query->where('title', 'like', "%{$groupFilter}%");
            }
        }

        $groups = $query->get();

        if ($groups->isEmpty()) {
            $this->warn('No matching Telegram groups found to sync.');
            return Command::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $full  = (bool) $this->option('full');
        $async = (bool) $this->option('queue');

        $this->info(sprintf(
            'Found %d group(s). Limit: %d msgs | Recursive: %s | Mode: %s',
            $groups->count(),
            $limit,
            $full ? 'Yes' : 'No',
            $async ? 'Background Queue' : 'Synchronous CLI'
        ));

        if ($async) {
            $delaySec = 0;
            foreach ($groups as $idx => $group) {
                SyncTelegramGroup::dispatch($group, $limit, $full)
                    ->delay(now()->addSeconds($delaySec));
                $this->line("  [Queued] {$group->title} (delay: +{$delaySec}s)");
                $delaySec += 3; // Stagger queue jobs by 3s to prevent Telegram flood limits
            }

            $this->newLine();
            $this->info('✓ All groups dispatched to background queue.');
            return Command::SUCCESS;
        }

        // Inline Synchronous Execution
        try {
            $client = $telegramClient->getClient();
            $client->start();
        } catch (\Throwable $e) {
            $this->error('Failed to initialize Telegram client: ' . $e->getMessage());
            Log::error('TelegramSync CLI failed to start client: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $results = [];

        foreach ($groups as $group) {
            $this->newLine();
            $this->line("<comment>Syncing: {$group->title} (ID: {$group->id})</comment>");

            $stats = ['scanned' => 0, 'new' => 0, 'updated' => 0, 'skipped' => 0];

            try {
                $messages = $telegramClient->getMessages($client, $group->invite_link, $limit);
                $stats['scanned'] = count($messages);

                $bar = $this->output->createProgressBar(count($messages));
                $bar->start();

                foreach (array_reverse($messages) as $msg) {
                    try {
                        $parsed = $parser->parse($msg);
                        if (!$parsed) {
                            $stats['skipped']++;
                        } else {
                            $res = $ingestor->ingest($group->id, $parsed, $client);
                            $action = $res['action'] ?? 'skipped';
                            $stats[$action] = ($stats[$action] ?? 0) + 1;
                        }
                    } catch (\Throwable $itemErr) {
                        $stats['skipped']++;
                        Log::warning("TelegramSync item error: " . $itemErr->getMessage());
                    }

                    $bar->advance();
                }

                $bar->finish();
                $this->newLine();

                $group->update(['last_synced_at' => now()]);

                $results[] = [
                    'id'      => $group->id,
                    'title'   => mb_substr($group->title, 0, 28),
                    'scanned' => $stats['scanned'],
                    'new'     => $stats['new'],
                    'updated' => $stats['updated'],
                    'skipped' => $stats['skipped'],
                ];

            } catch (\Throwable $e) {
                $this->error("Failed syncing {$group->title}: " . $e->getMessage());
                Log::error("TelegramSync group error [{$group->id}]: " . $e->getMessage());
            }

            // Small delay between groups
            usleep(200000); // 0.2s
        }

        $this->newLine();
        $this->table(['ID', 'Group Title', 'Scanned', 'New Products', 'Updated Prices', 'Skipped'], $results);
        $this->info('✓ Synchronous sync completed.');

        return Command::SUCCESS;
    }
}

