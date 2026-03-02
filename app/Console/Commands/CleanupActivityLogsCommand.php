<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanupActivityLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity-logs:cleanup
                            {--days= : Number of days to keep logs (overrides env)}
                            {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up activity logs based on retention configuration from .env';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get configuration from .env or command option
        $retentionDays = $this->option('days') ?: env('LAMA_HARI_PENYIMPANAN_LOG', 90);
        $autoCleanup = env('PEMBERSIHAN_OTOMATIS_LOG', 'true') === 'true';
        $archiveEnabled = env('ARSIP_LOG_DIAKTIFKAN', 'false') === 'true';
        $archiveAfterDays = env('ARSIP_LOG_SETELAH_HARI', 180);

        $this->info("Activity Logs Cleanup Configuration:");
        $this->info("  Retention Days: {$retentionDays}");
        $this->info("  Auto Cleanup: " . ($autoCleanup ? 'Enabled' : 'Disabled'));
        $this->info("  Archive Enabled: " . ($archiveEnabled ? 'Yes' : 'No'));
        if ($archiveEnabled) {
            $this->info("  Archive After Days: {$archiveAfterDays}");
        }
        $this->newLine();

        // Check if auto cleanup is enabled
        if (!$autoCleanup && !$this->option('force')) {
            $this->warn('Automatic log cleanup is disabled in .env (PEMBERSIHAN_OTOMATIS_LOG=false)');
            $this->warn('Use --force flag to run cleanup manually');

            Log::warning('[Activity Logs Cleanup] Skipped - Auto cleanup disabled');

            return self::SUCCESS;
        }

        // Confirm cleanup unless --force is used
        if (!$this->option('force') && !$this->confirm("Delete activity logs older than {$retentionDays} days?")) {
            $this->info('Cleanup cancelled');

            Log::info('[Activity Logs Cleanup] Cancelled by user');

            return self::SUCCESS;
        }

        $this->info("Starting cleanup of activity logs older than {$retentionDays} days...");

        try {
            $cutoffDate = Carbon::now()->subDays($retentionDays);

            $this->info("Cutoff Date: {$cutoffDate->toDateTimeString()}");

            // Count logs to be deleted
            $logsToDelete = DB::table('activity_logs')
                ->where('created_at', '<', $cutoffDate)
                ->count();

            if ($logsToDelete === 0) {
                $this->info('No logs to clean up');

                Log::info('[Activity Logs Cleanup] No logs to clean up', [
                    'retention_days' => $retentionDays,
                    'cutoff_date' => $cutoffDate->toDateTimeString()
                ]);

                return self::SUCCESS;
            }

            $this->info("Found {$logsToDelete} logs to delete");

            // Handle archiving if enabled
            if ($archiveEnabled) {
                $this->info("Archive is enabled - logs will be archived before deletion");

                $archiveCutoff = Carbon::now()->subDays($archiveAfterDays);
                $logsToArchive = DB::table('activity_logs')
                    ->where('created_at', '<', $cutoffDate)
                    ->where('created_at', '>=', $archiveCutoff)
                    ->get();

                if ($logsToArchive->count() > 0) {
                    $this->info("Archiving {$logsToArchive->count()} logs...");

                    // TODO: Implement archiving logic here
                    // This would typically involve:
                    // 1. Export to file/storage
                    // 2. Compress the archive
                    // 3. Store in archives directory or S3

                    $this->warn('Archive functionality not yet implemented');
                    Log::warning('[Activity Logs Cleanup] Archive enabled but not implemented', [
                        'logs_to_archive' => $logsToArchive->count()
                    ]);
                }
            }

            // Delete old logs
            $deleted = DB::table('activity_logs')
                ->where('created_at', '<', $cutoffDate)
                ->delete();

            $this->info("✓ Successfully deleted {$deleted} activity logs");

            Log::info('[Activity Logs Cleanup] Completed successfully', [
                'deleted_count' => $deleted,
                'retention_days' => $retentionDays,
                'cutoff_date' => $cutoffDate->toDateTimeString()
            ]);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("✗ Failed to cleanup activity logs: {$e->getMessage()}");

            Log::error('[Activity Logs Cleanup] Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return self::FAILURE;
        }
    }
}
