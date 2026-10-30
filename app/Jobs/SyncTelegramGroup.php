<?php

namespace App\Jobs;

use App\Models\TelegramGroup;
use App\Services\IngestMessageService;
use App\Services\MessageParserService;
use App\Services\TelegramClientService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncTelegramGroup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Allow the job to run for up to 15 minutes since downloading many photos takes time
    public int $timeout = 900;
    public int $tries = 3;
    public int $maxExceptions = 3;

    public int $limit = 200;
    public bool $recursive = false;
    public int $offsetId = 0;

    /**
     * Exponential backoff in seconds for retries.
     */
    public function backoff(): array
    {
        return [30, 60, 180];
    }

    public function __construct(
        public TelegramGroup $group,
        int $limit = 200,
        bool $recursive = false,
        int $offsetId = 0
    ) {
        $this->limit = $limit;
        $this->recursive = $recursive;
        $this->offsetId = $offsetId;
    }

    public function handle(
        TelegramClientService $telegramClient,
        MessageParserService $parser,
        IngestMessageService $ingestor
    ): void {
        Log::info("Starting background sync for group: {$this->group->title} (Limit: {$this->limit}, Offset: {$this->offsetId}, Recursive: " . ($this->recursive ? 'Yes' : 'No') . ")");

        if (!$this->group->is_active) {
            Log::info("Group [{$this->group->id}] {$this->group->title} is paused (inactive). Skipping sync.");
            return;
        }

        $this->group->update(['sync_status' => 'syncing']);

        try {
            $client = null;
            if ($telegramClient->isInviteLink($this->group->invite_link)) {
                try {
                    $client = $telegramClient->getClient();
                    $client->start();
                } catch (\Throwable $e) {
                    Log::info("SyncTelegramGroup MTProto notice: {$e->getMessage()}. Using fast web channel crawler.");
                    $client = null;
                }
            }

            $messages = $telegramClient->getMessages($client, $this->group->invite_link, $this->limit, $this->offsetId);
            $stats    = ['new' => 0, 'updated' => 0, 'skipped' => 0];

            $thirtyDaysAgo = strtotime('-30 days');
            $reachedLimit = false;

            // Process oldest to newest within this batch
            foreach (array_reverse($messages) as $msg) {
                // If a message is older than 30 days, skip processing and downloading
                if (isset($msg['date']) && $msg['date'] < $thirtyDaysAgo) {
                    $reachedLimit = true;
                    continue;
                }

                try {
                    $parsed = $parser->parse($msg);
                    if (!$parsed) continue;

                    $result = $ingestor->ingest($this->group->id, $parsed, $client);
                    $stats[$result['action']] = ($stats[$result['action']] ?? 0) + 1;
                } catch (\Throwable $itemErr) {
                    Log::warning("Error ingesting message [{$msg['id']}] for {$this->group->title}: " . $itemErr->getMessage());
                }
            }

            $this->group->update([
                'last_synced_at' => now(),
                'sync_status'    => 'idle',
                'last_error'     => null,
            ]);

            Log::info("Background sync completed for {$this->group->title}", $stats);

            // If recursive is enabled and we fetched some messages, continue crawling history
            if ($this->recursive && count($messages) > 0) {
                if ($reachedLimit) {
                    Log::info("30-day historical limit reached for {$this->group->title}. Stopping recursive sync.");
                } else {
                    $lastMsgId = end($messages)['id'] ?? 0;
                    if ($lastMsgId > 1) { // 1 is usually the creation message of the channel
                        Log::info("Dispatching next batch for {$this->group->title} starting from offset ID: {$lastMsgId}");
                        // Dispatch next chunk with 2-second delay to prevent Telegram rate limit flooding
                        self::dispatch($this->group, $this->limit, true, $lastMsgId)->delay(now()->addSeconds(2));
                    }
                }
            }

        } catch (\Throwable $e) {
            // Check for Telegram FLOOD_WAIT and release job back to queue
            if (preg_match('/FLOOD_WAIT_(\d+)/i', $e->getMessage(), $m)) {
                $seconds = (int) $m[1];
                Log::warning("Telegram FLOOD_WAIT: {$seconds}s required for {$this->group->title}. Releasing job to queue.");
                $this->release($seconds + 5);
                return;
            }

            Log::error("Background sync failed for {$this->group->title}: " . $e->getMessage());
            $this->group->update([
                'sync_status' => 'failed',
                'last_error'  => mb_substr($e->getMessage(), 0, 500),
            ]);
            throw $e; // Re-throw to trigger retry policy
        }
    }

    /**
     * Handle job failure after max tries.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error("SyncTelegramGroup job permanently failed for group {$this->group->id} ({$this->group->title}): " . $exception?->getMessage());
    }
}
