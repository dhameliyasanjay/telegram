<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Scheduled Tasks
 * Automatically executes the unified Telegram sync, product deduplication,
 * and canonical price comparison updates every 1 hour.
 */
Schedule::command('telegram:hourly-sync --limit=100')
    ->hourly()
    ->withoutOverlapping(60)
    ->runInBackground();
