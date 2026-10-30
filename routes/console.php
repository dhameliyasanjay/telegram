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
 * and canonical price comparison updates based on the configured interval in minutes.
 */
$syncMinutes = 15;
try {
    if (class_exists(\App\Services\SettingService::class)) {
        $syncMinutes = (int) app(\App\Services\SettingService::class)->get('sync_interval_minutes', 15);
    }
} catch (\Throwable $e) {
    $syncMinutes = 15;
}

if ($syncMinutes <= 0) {
    $syncMinutes = 15;
}

$task = Schedule::command('telegram:hourly-sync --limit=100');

if ($syncMinutes === 1) {
    $task->everyMinute();
} elseif ($syncMinutes === 2) {
    $task->everyTwoMinutes();
} elseif ($syncMinutes === 3) {
    $task->everyThreeMinutes();
} elseif ($syncMinutes === 4) {
    $task->everyFourMinutes();
} elseif ($syncMinutes === 5) {
    $task->everyFiveMinutes();
} elseif ($syncMinutes === 10) {
    $task->everyTenMinutes();
} elseif ($syncMinutes === 15) {
    $task->everyFifteenMinutes();
} elseif ($syncMinutes === 30) {
    $task->everyThirtyMinutes();
} elseif ($syncMinutes % 60 === 0) {
    $hours = max(1, (int) ($syncMinutes / 60));
    $task->cron("0 */{$hours} * * *");
} else {
    $task->cron("*/{$syncMinutes} * * * *");
}

$task->withoutOverlapping(60)->runInBackground();
