<?php

namespace App\Console\Commands;

use App\Jobs\HourlyTelegramSyncJob;
use Illuminate\Console\Command;

class RunHourlySync extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'telegram:hourly-sync 
                            {--limit=100 : Number of messages to fetch per group} 
                            {--queue : Dispatch to background queue instead of running inline}';

    /**
     * The console command description.
     */
    protected $description = 'Run the unified hourly Telegram sync, product deduplication, and price comparison update';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $this->info("Initiating Hourly Telegram Sync (Limit: {$limit} msgs/group)...");

        if ($this->option('queue')) {
            HourlyTelegramSyncJob::dispatch($limit);
            $this->info("✓ HourlyTelegramSyncJob successfully queued for background execution.");
            return self::SUCCESS;
        }

        // Execute inline synchronously
        $this->info("Running sync inline. Check storage/logs/laravel.log for detailed progress...");
        dispatch_sync(new HourlyTelegramSyncJob($limit));
        $this->info("✓ Hourly sync completed successfully!");

        return self::SUCCESS;
    }
}

