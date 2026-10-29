<?php

namespace App\Jobs;

use App\Models\CanonicalProduct;
use App\Models\Product;
use App\Models\TelegramGroup;
use App\Services\IngestMessageService;
use App\Services\MessageParserService;
use App\Services\ProductIdentityService;
use App\Services\TelegramClientService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class HourlyTelegramSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // Allow up to 30 minutes
    public int $tries = 2;

    public function __construct(
        public int $messageLimit = 100
    ) {}

    /**
     * Execute the hourly sync job.
     */
    public function handle(
        TelegramClientService $telegramClient,
        MessageParserService $parser,
        IngestMessageService $ingestor,
        ProductIdentityService $identityService
    ): void {
        $startTime = microtime(true);
        Log::info("=== Starting Hourly Telegram Sync Job ===");

        $groups = TelegramGroup::where('is_active', true)->get();
        $summary = [
            'groups_total'     => $groups->count(),
            'groups_synced'    => 0,
            'messages_scanned' => 0,
            'new_products'     => 0,
            'price_updates'    => 0,
            'skipped'          => 0,
            'merged_duplicates'=> 0,
            'errors'           => 0,
        ];

        if ($groups->isEmpty()) {
            Log::warning("HourlyTelegramSyncJob: No Telegram groups found in database.");
            return;
        }

        // ── 1. Connect to Telegram MTProto Client ────────────────────────────
        $client = null;
        try {
            $client = $telegramClient->getClient();
            $client->start();
        } catch (\Throwable $e) {
            Log::error("HourlyTelegramSyncJob: Failed to connect Telegram client: " . $e->getMessage());

            if (preg_match('/FLOOD_WAIT_(\d+)/i', $e->getMessage(), $m)) {
                $seconds = (int) $m[1];
                Log::warning("HourlyTelegramSyncJob: FLOOD_WAIT_{$seconds}s. Releasing job with delay.");
                $this->release($seconds + 10);
                return;
            }

            throw $e;
        }

        // ── 2. Ingest Messages from Each Group ───────────────────────────────
        $thirtyDaysAgo = strtotime('-30 days');

        foreach ($groups as $group) {
            try {
                Log::info("HourlyTelegramSyncJob: Fetching messages for [{$group->id}] {$group->title} (Limit: {$this->messageLimit})");
                $messages = $telegramClient->getMessages($client, $group->invite_link, $this->messageLimit);
                $summary['messages_scanned'] += count($messages);

                foreach (array_reverse($messages) as $msg) {
                    if (isset($msg['date']) && $msg['date'] < $thirtyDaysAgo) {
                        continue;
                    }

                    try {
                        $parsed = $parser->parse($msg);
                        if (!$parsed) {
                            $summary['skipped']++;
                            continue;
                        }

                        $res = $ingestor->ingest($group->id, $parsed, $client);
                        $action = $res['action'] ?? 'skipped';

                        if ($action === 'new') {
                            $summary['new_products']++;
                        } elseif ($action === 'updated') {
                            $summary['price_updates']++;
                        } else {
                            $summary['skipped']++;
                        }
                    } catch (\Throwable $msgEx) {
                        $summary['errors']++;
                        Log::warning("HourlyTelegramSyncJob: Error on message in group [{$group->id}]: " . $msgEx->getMessage());
                    }
                }

                $group->update(['last_synced_at' => now()]);
                $summary['groups_synced']++;

                // Small delay between groups to avoid Telegram rate limits
                usleep(250000); // 0.25s

            } catch (\Throwable $groupEx) {
                $summary['errors']++;
                Log::error("HourlyTelegramSyncJob: Error syncing group [{$group->id}] {$group->title}: " . $groupEx->getMessage());
            }
        }

        // ── 3. Rule A Deduplication Check Across All Groups ──────────────────
        Log::info("HourlyTelegramSyncJob: Enforcing Rule A within-group deduplication...");
        foreach ($groups as $group) {
            try {
                $merged = $identityService->deduplicateGroupOffers($group->id);
                if ($merged > 0) {
                    $summary['merged_duplicates'] += $merged;
                    Log::info("HourlyTelegramSyncJob: Merged {$merged} duplicate offers in group {$group->title}");
                }
            } catch (\Throwable $dedupEx) {
                Log::error("HourlyTelegramSyncJob: Deduplication error in group [{$group->id}]: " . $dedupEx->getMessage());
            }
        }

        // ── 4. Refresh Canonical Product Aggregates ──────────────────────────
        Log::info("HourlyTelegramSyncJob: Refreshing canonical product aggregates...");
        try {
            $identityService->refreshAllCanonicalStats();
        } catch (\Throwable $statsEx) {
            Log::error("HourlyTelegramSyncJob: Error refreshing canonical stats: " . $statsEx->getMessage());
        }

        $elapsed = round(microtime(true) - $startTime, 2);
        Log::info("=== Hourly Telegram Sync Job Finished in {$elapsed}s ===", $summary);
    }

    /**
     * Handle job failure after max retries.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error("HourlyTelegramSyncJob permanently failed: " . $exception?->getMessage());
    }
}

