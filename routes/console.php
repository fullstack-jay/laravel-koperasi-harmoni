<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule activity logs cleanup every week (Sunday at 2 AM)
Schedule::command('activity-logs:cleanup --days=90 --force')
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->description('Clean up activity logs older than 90 days')
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Activity logs cleanup completed successfully');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Activity logs cleanup failed');
    });
