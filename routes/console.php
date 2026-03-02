<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Stock truncate command
Artisan::command('stock:truncate', function () {
    if ($this->confirm('Are you sure you want to truncate the stock_items table? This will delete ALL stock items.')) {
        DB::table('stock_items')->truncate();

        $this->info('Stock items table truncated successfully.');

        return 0;
    }

    $this->info('Operation cancelled.');

    return 1;
})->purpose('Truncate the stock_items table');

// Schedule activity logs cleanup based on .env configuration
$scheduleType = env('JADWAL_PEMBERSIHAN_LOG', 'daily');
$scheduleTime = env('WAKTU_PEMBERSIHAN_LOG', '02:00');
$autoCleanup = env('PEMBERSIHAN_OTOMATIS_LOG', 'true') === 'true';

if ($autoCleanup) {
    $schedule = Schedule::command('activity-logs:cleanup --force');

    // Set schedule frequency based on .env configuration
    switch ($scheduleType) {
        case 'hourly':
            $schedule->hourly();
            break;
        case 'daily':
            $schedule->daily()->at($scheduleTime);
            break;
        case 'weekly':
            $schedule->weekly()->sundays()->at($scheduleTime);
            break;
        case 'monthly':
            $schedule->monthly()->at($scheduleTime);
            break;
        default:
            $schedule->daily()->at($scheduleTime);
    }

    $schedule->description("Clean up activity logs ({$scheduleType} at {$scheduleTime})")
        ->onSuccess(function () {
            \Illuminate\Support\Facades\Log::info('[Scheduler] Activity logs cleanup completed successfully');
        })
        ->onFailure(function () {
            \Illuminate\Support\Facades\Log::error('[Scheduler] Activity logs cleanup failed');
        });
}

