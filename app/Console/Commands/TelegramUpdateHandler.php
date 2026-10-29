<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TelegramGroup;
use App\Services\IngestMessageService;
use App\Services\MessageParserService;
use danog\MadelineProto\EventHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * MadelineProto event handler class.
 * Since it is instantiated by MadelineProto, we use Laravel's app() helper
 * to resolve our services.
 */
class TelegramUpdateHandler extends EventHandler
{
    public static ?Command $command = null;

    private ?MessageParserService $parser = null;
    private ?IngestMessageService $ingestor = null;

    // Cache of telegram_id => DB group id
    private array $groupMap = [];
    private int $lastMapUpdate = 0;

    public function onStart(): void
    {
        // Resolve services from Laravel container
        $this->parser   = app(MessageParserService::class);
        $this->ingestor = app(IngestMessageService::class);
        $this->updateGroupMap();
        
        if (self::$command) {
            self::$command->info('✓ Event loop started and listening for new messages.');
        }
    }

    public function onUpdateNewMessage(array $update): void
    {
        try {
            $this->handleUpdate($update);
        } catch (\Throwable $e) {
            Log::error('TelegramUpdateHandler error on new message: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            if (self::$command) {
                self::$command->warn('Error processing message: ' . $e->getMessage());
            }
        }
    }

    public function onUpdateNewChannelMessage(array $update): void
    {
        try {
            $this->handleUpdate($update);
        } catch (\Throwable $e) {
            Log::error('TelegramUpdateHandler error on channel message: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            if (self::$command) {
                self::$command->warn('Error processing channel message: ' . $e->getMessage());
            }
        }
    }

    private function handleUpdate(array $update): void
    {
        $message = $update['message'] ?? [];
        if (empty($message)) return;

        $peer = $message['peer_id'] ?? $message['to_id'] ?? [];
        $rawId = abs($peer['channel_id'] ?? $peer['chat_id'] ?? 0);
        if (!$rawId) return;

        // Try getting unified Bot API ID from MadelineProto, otherwise compute prefix candidate
        $chatId = $rawId;
        try {
            if (method_exists($this, 'getId')) {
                $chatId = abs((int) $this->getId($peer));
            }
        } catch (\Throwable $e) {
            $chatId = $rawId;
        }

        // Periodically refresh the group map (every 60 seconds)
        if (time() - $this->lastMapUpdate > 60) {
            $this->updateGroupMap();
        }

        // Match against Bot API ID or raw channel ID
        $dbGroupId = $this->groupMap[$chatId] ?? $this->groupMap[$rawId] ?? null;

        // If not in cache, refresh once to check if a new group was just added
        if (!$dbGroupId) {
            $this->updateGroupMap();
            $dbGroupId = $this->groupMap[$chatId] ?? $this->groupMap[$rawId] ?? null;
        }

        if (!$dbGroupId) {
            return; // Not a monitored group
        }

        $parsed = $this->parser->parse($message);
        if (!$parsed) return;

        $result = $this->ingestor->ingest($dbGroupId, $parsed, $this->getAPI());

        if ($result['action'] !== 'skipped' && self::$command) {
            self::$command->line(
                sprintf(
                    '[%s] %s — %s (price: %s)',
                    now()->format('H:i:s'),
                    $result['action'],
                    $parsed['product_name'] ?? '?',
                    $parsed['price'] ?? 'N/A'
                )
            );
        }
    }

    private function updateGroupMap(): void
    {
        $map = [];
        $groups = TelegramGroup::all(['id', 'telegram_id']);

        foreach ($groups as $g) {
            $tid = (int) $g->telegram_id;
            $map[$tid] = $g->id;

            // Also store without '100' prefix so raw channel IDs also match
            $tidStr = (string) $tid;
            if (str_starts_with($tidStr, '100')) {
                $raw = (int) substr($tidStr, 3);
                $map[$raw] = $g->id;
            }
        }

        $this->groupMap = $map;
        $this->lastMapUpdate = time();
    }
}
