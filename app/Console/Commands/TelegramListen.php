<?php

namespace App\Console\Commands;

use App\Models\TelegramGroup;
use App\Services\IngestMessageService;
use App\Services\MessageParserService;
use App\Services\TelegramClientService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TelegramListen extends Command
{
    protected $signature   = 'telegram:listen';
    protected $description = 'Start the live Telegram message listener (long-running daemon)';

    public function __construct(
        private readonly TelegramClientService $telegramClient,
        private readonly MessageParserService  $parser,
        private readonly IngestMessageService  $ingestor,
    ) {
        parent::__construct();
    }

    public function handle(): int
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
        $this->info('Connecting to Telegram...');

        try {
            $client = $this->telegramClient->getClient();
            $client->start();

            // Build a map of telegram_id → DB id for fast lookup
            $groupMap = $groups->keyBy('telegram_id')->toArray();

            $self = $this;

            // Register the NewMessage event handler
            $client->loop(function () use ($client, &$groupMap, $self) {
                $client->setEventHandler(new class($client, $groupMap, $self->parser, $self->ingestor, $self) extends \danog\MadelineProto\EventHandler {
                    public function __construct(
                        \danog\MadelineProto\API $client,
                        private array &$groupMap,
                        private MessageParserService $parser,
                        private IngestMessageService $ingestor,
                        private Command $command,
                    ) {
                        parent::__construct($client);
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

                        // Re-load groups periodically to pick up newly added ones
                        if (!isset($this->groupMap[$chatId])) {
                            // Check DB for newly added group
                            $newGroup = TelegramGroup::where('telegram_id', $chatId)->first();
                            if ($newGroup) {
                                $this->groupMap[$chatId] = $newGroup->toArray();
                            } else {
                                return; // Not a monitored group
                            }
                        }

                        $dbGroupId = $this->groupMap[$chatId]['id'] ?? null;
                        if (!$dbGroupId) return;

                        $parsed = $this->parser->parse($message);
                        if (!$parsed) return;

                        $result = $this->ingestor->ingest($dbGroupId, $parsed, null);

                        if ($result['action'] !== 'skipped') {
                            $this->command->line(
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
                });
            });

        } catch (\Throwable $e) {
            $this->error('Listener failed: ' . $e->getMessage());
            Log::error('TelegramListen error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
