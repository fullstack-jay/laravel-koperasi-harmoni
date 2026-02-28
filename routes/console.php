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
