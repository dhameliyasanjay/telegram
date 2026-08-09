<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TelegramGroup;
use App\Services\IngestMessageService;
use App\Services\MessageParserService;
use App\Services\TelegramClientService;
use danog\MadelineProto\EventHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TelegramListen extends Command
{
    protected $signature   = 'telegram:listen';
    protected $description = 'Start the live Telegram message listener (long-running daemon)';

    public function handle(TelegramClientService $telegramClient): int
    {
        $this->info('═══════════════════════════════════════════');
        $this->info('  Telegram Price Monitor — Live Listener');
        $this->info('═══════════════════════════════════════════');

        $groups = TelegramGroup::all();

        if ($groups->isEmpty()) {
            $this->warn('No groups in database. Add a group via POST /api/groups first.');
            $this->warn('Waiting for groups to be added... (Ctrl+C to exit)');
        } else {
            $this->info("Monitoring {$groups->count()} group(s):");
            foreach ($groups as $g) {
                $this->line("  • [{$g->telegram_id}] {$g->title}");
            }
        }

        $this->info('');
        $this->info('Connecting to Telegram and starting event loop...');

        try {
            // Setting a static property on the handler so it can log to console
            TelegramUpdateHandler::$command = $this;

            // In MadelineProto v8, startAndLoop is called statically on API
            // and takes the class name of the event handler.
            // We pass the session path in the constructor array or just use the client directly?
            // Actually, we can instantiate the API, and then call startAndLoop?
            // MadelineProto v8 allows $client->startAndLoop(TelegramUpdateHandler::class);

            TelegramUpdateHandler::startAndLoop(
                $telegramClient->getSessionPath(),
                $telegramClient->getSettings()
            );

        } catch (\Throwable $e) {
            $this->error('Listener failed: ' . $e->getMessage());
            Log::error('TelegramListen error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

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
        $this->handleUpdate($update);
    }

    public function onUpdateNewChannelMessage(array $update): void
    {
        $this->handleUpdate($update);
    }

    private function handleUpdate(array $update): void
    {
        $message = $update['message'] ?? [];
        if (empty($message)) return;

        $peer = $message['peer_id'] ?? $message['to_id'] ?? [];
        $chatId = abs($peer['channel_id'] ?? $peer['chat_id'] ?? 0);

        if (!$chatId) return;

        // Periodically update the group map (every 60 seconds) so we don't query DB on every message
        if (time() - $this->lastMapUpdate > 60) {
            $this->updateGroupMap();
        }

        // If it's a new group that isn't cached yet, fetch it immediately
        if (!isset($this->groupMap[$chatId])) {
            $newGroup = TelegramGroup::where('telegram_id', $chatId)->first();
            if ($newGroup) {
                $this->groupMap[$chatId] = $newGroup->id;
            } else {
                return; // Not a monitored group
            }
        }

        $dbGroupId = $this->groupMap[$chatId];

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
        $this->groupMap = TelegramGroup::pluck('id', 'telegram_id')->toArray();
        $this->lastMapUpdate = time();
    }
}
