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
    public $timeout = 900; 

    public int $limit = 200;
    public bool $recursive = false;
    public int $offsetId = 0;

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

        try {
            $client = $telegramClient->getClient();
            $client->start();

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

                $parsed = $parser->parse($msg);
                if (!$parsed) continue;

                $result = $ingestor->ingest($this->group->id, $parsed, $client);
                $stats[$result['action']] = ($stats[$result['action']] ?? 0) + 1;
            }

            $this->group->update(['last_synced_at' => now()]);

            Log::info("Background sync completed for {$this->group->title}", $stats);

            // If recursive is enabled and we fetched some messages, there are probably more messages
            // We only continue recursion if we haven't hit the 30-day limit
            if ($this->recursive && count($messages) > 0) {
                if ($reachedLimit) {
                    Log::info("30-day historical limit reached for {$this->group->title}. Stopping recursive sync.");
                } else {
                    $lastMsgId = end($messages)['id'] ?? 0;
                    if ($lastMsgId > 1) { // 1 is usually the creation message of the channel
                        Log::info("Dispatching next batch for {$this->group->title} starting from offset ID: {$lastMsgId}");
                        // Dispatch next chunk
                        self::dispatch($this->group, $this->limit, true, $lastMsgId);
                    }
                }
            }

        } catch (\Throwable $e) {
            Log::error("Background sync failed for {$this->group->title}: " . $e->getMessage());
            throw $e; // Re-throw to mark job as failed
        }
    }
}
