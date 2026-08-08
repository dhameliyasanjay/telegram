<?php

namespace App\Console\Commands;

use App\Services\TelegramClientService;
use Illuminate\Console\Command;

class TelegramLogin extends Command
{
    protected $signature   = 'telegram:login';
    protected $description = 'One-time interactive login to Telegram via MTProto (saves session for future runs)';

    public function __construct(private readonly TelegramClientService $telegramClient)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════');
        $this->info('  Telegram MTProto — One-Time Login');
        $this->info('═══════════════════════════════════════');
        $this->info('');
        $this->info('This will log in using your TELEGRAM_API_ID and TELEGRAM_API_HASH.');
        $this->info('You will be prompted for your phone number and a one-time OTP code.');
        $this->info('The session will be saved and future runs will be non-interactive.');
        $this->info('');

        try {
            $client = $this->telegramClient->getClient();

            // MadelineProto's start() method handles the full auth flow interactively
            // When run in CLI it will prompt for phone + OTP automatically
            $client->start();

            $this->info('');
            $this->info('✓ Login successful! Session saved.');
            $this->info('  Session path: ' . storage_path('app/telegram/session.madeline'));
            $this->info('');
            $this->info('You can now run:');
            $this->info('  php artisan telegram:listen   — start the live message listener');
            $this->info('');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Login failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
