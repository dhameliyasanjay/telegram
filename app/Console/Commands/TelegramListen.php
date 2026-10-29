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
