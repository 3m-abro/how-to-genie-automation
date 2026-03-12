<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Failure monitor: alerts to Telegram when any n8n workflow execution fails. Cron must run: * * * * * php artisan schedule:run
Schedule::command('n8n:check-failures')->everyFiveMinutes();

Schedule::command('weekly:summary')
    ->weeklyOn(
        config('services.weekly_summary.day', 0),
        config('services.weekly_summary.time', '08:00')
    );
