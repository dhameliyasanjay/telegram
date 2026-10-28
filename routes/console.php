<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Scheduled Tasks
 * Automatically queues recent product syncs for all monitored groups hourly.
 */
Schedule::command('telegram:sync --limit=50 --queue')
    ->hourly()
    ->withoutOverlapping(60)
    ->runInBackground();
