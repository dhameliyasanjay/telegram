<?php

namespace App\Console\Commands;

use App\Models\GroupSubmission;
use App\Models\TelegramGroup;
use App\Services\IngestMessageService;
use App\Services\MessageParserService;
use App\Services\TelegramClientService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ConnectAndSyncTelegramGroupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:connect-and-sync 
                            {group_id : Database ID of the TelegramGroup} 
                            {submission_id? : Optional ID of the GroupSubmission to update}
                            {--limit=100 : Number of messages to ingest initially}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Join a Telegram channel, fetch full metadata, and ingest its initial products in the background';

    /**
     * Execute the console command.
     */
    public function handle(
        TelegramClientService $telegramClient,
        MessageParserService $parser,
        IngestMessageService $ingestor
    ): int {
        $groupId = (int) $this->argument('group_id');
        $group   = TelegramGroup::find($groupId);

        if (!$group) {
            $this->error("TelegramGroup with ID [{$groupId}] not found.");
            Log::error("telegram:connect-and-sync: Group [{$groupId}] not found.");
            return Command::FAILURE;
        }

        $submissionId = $this->argument('submission_id');
        $submission   = $submissionId ? GroupSubmission::find($submissionId) : null;
        $limit        = max(1, (int) $this->option('limit'));

        $this->info("Starting background connect & sync for: {$group->title} ({$group->invite_link})");
        Log::info("telegram:connect-and-sync: Starting for group [{$group->id}] {$group->title} ({$group->invite_link})");

        $group->update(['sync_status' => 'syncing']);

        try {
            $client = null;
            // Only attempt MTProto if this is a private invite link (t.me/+hash or t.me/joinchat/...)
            if ($telegramClient->isInviteLink($group->invite_link)) {
                try {
                    $client = $telegramClient->getClient();
                    $client->start();
                    $this->info("Joining channel via MTProto client...");
                    $telegramClient->joinGroup($client, $group->invite_link);
                } catch (\Throwable $clientErr) {
                    Log::info("ConnectAndSyncTelegramGroupCommand MTProto notice: " . $clientErr->getMessage());
                    $client = null;
                }
            }

            // 2. Fetch metadata
            $this->info("Fetching metadata from Telegram...");
            $info = $telegramClient->fetchFastMetadata($group->invite_link);
            if ((empty($info) || empty($info['title'])) && $client) {
                try {
                    $info = $telegramClient->getGroupInfo($client, $group->invite_link);
                } catch (\Throwable $infoEx) {
                    Log::warning("telegram:connect-and-sync getGroupInfo notice: " . $infoEx->getMessage());
                }
            }

            $updateData = [
                'is_active'   => true,
                'sync_status' => 'syncing',
            ];

            if (!empty($info['telegram_id'])) {
                $updateData['telegram_id'] = $info['telegram_id'];
            }
            if (!empty($info['title'])) {
                $updateData['title'] = $info['title'];
            }
            if (isset($info['member_count']) && $info['member_count'] > 0) {
                $updateData['member_count'] = $info['member_count'];
            }
            if (!empty($info['photo_url'])) {
                $updateData['photo_url'] = $info['photo_url'];
            }

            $group->update($updateData);

            if ($submission && !empty($info['title'])) {
                $submission->update(['group_title' => $info['title']]);
            }

            $this->info("Metadata updated: {$group->title} (Telegram ID: {$group->telegram_id})");

            // 3. Ingest messages & products
            $this->info("Ingesting latest {$limit} messages...");
            $messages = $telegramClient->getMessages($client, $group->invite_link, $limit);
            $stats    = ['scanned' => count($messages), 'new' => 0, 'updated' => 0, 'skipped' => 0];

            $thirtyDaysAgo = strtotime('-30 days');

            foreach (array_reverse($messages) as $msg) {
                if (isset($msg['date']) && $msg['date'] < $thirtyDaysAgo) {
                    continue;
                }

                try {
                    $parsed = $parser->parse($msg);
                    if (!$parsed) {
                        $stats['skipped']++;
                        continue;
                    }

                    $res = $ingestor->ingest($group->id, $parsed, $client);
                    $action = $res['action'] ?? 'skipped';
                    $stats[$action] = ($stats[$action] ?? 0) + 1;
                } catch (\Throwable $itemErr) {
                    $stats['skipped']++;
                    Log::warning("telegram:connect-and-sync item error: " . $itemErr->getMessage());
                }
            }

            $group->update([
                'sync_status'    => 'idle',
                'last_synced_at' => now(),
                'last_error'     => null,
            ]);

            $this->info("✓ Successfully connected and synced group: {$group->title}. Ingested {$stats['new']} new products, {$stats['updated']} updates.");
            Log::info("telegram:connect-and-sync: Successfully completed for group [{$group->id}] {$group->title}", $stats);

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("Error connecting & syncing group [{$group->id}]: " . $e->getMessage());
            Log::error("telegram:connect-and-sync: Failed for group [{$group->id}]: " . $e->getMessage());

            $group->update([
                'sync_status' => 'failed',
                'last_error'  => mb_substr($e->getMessage(), 0, 500),
            ]);

            return Command::FAILURE;
        }
    }
}

