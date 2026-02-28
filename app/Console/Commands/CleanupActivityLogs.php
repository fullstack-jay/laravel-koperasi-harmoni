<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\V1\Logging\Model\ActivityLog;

final class CleanupActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity-logs:cleanup
                            {--days=90 : Number of days to keep logs}
                            {--force : Force cleanup without confirmation}
                            {--archive : Archive to activity_logs_archive before deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old activity logs to prevent database bloat';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $daysToKeep = (int) $this->option('days');
        $force = $this->option('force');
        $archive = $this->option('archive');

        $cutoffDate = now()->subDays($daysToKeep);
        $count = ActivityLog::where('created_at', '<', $cutoffDate)->count();

        if ($count === 0) {
            $this->info('No old activity logs to clean up.');
            return self::SUCCESS;
        }

        $this->warn("Found {$count} activity logs older than {$daysToKeep} days (before {$cutoffDate}).");

        if (!$force && !$this->confirm('Do you wish to delete these old logs?')) {
            $this->info('Cleanup cancelled.');
            return self::SUCCESS;
        }

        try {
            // Archive if requested
            if ($archive) {
                $this->archiveLogs($cutoffDate);
            }

            // Delete old logs
            $deletedCount = ActivityLog::where('created_at', '<', $cutoffDate)->delete();

            $this->info("Successfully deleted {$deletedCount} old activity logs.");

            Log::info('Activity log cleanup completed', [
                'deleted_records' => $deletedCount,
                'days_kept' => $daysToKeep,
                'archived' => $archive,
            ]);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Cleanup failed: {$e->getMessage()}");
            Log::error('Activity log cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Archive logs to archive table before deletion.
     */
    private function archiveLogs($cutoffDate): void
    {
        $this->info('Archiving old logs...');

        // Create archive table if not exists
        $this->ensureArchiveTableExists();

        // Copy old logs to archive table
        DB::statement("
            INSERT INTO activity_logs_archive
            SELECT * FROM activity_logs
            WHERE created_at < ?
        ", [$cutoffDate]);

        $archivedCount = DB::table('activity_logs_archive')
            ->where('created_at', '<', $cutoffDate)
            ->count();

        $this->info("Archived {$archivedCount} logs to activity_logs_archive table.");
    }

    /**
     * Ensure archive table exists.
     */
    private function ensureArchiveTableExists(): void
    {
        $tableName = 'activity_logs_archive';

        $exists = DB::select("SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = '{$tableName}'
        )");

        if (!$exists || !$exists[0]->exists) {
            $this->info("Creating archive table: {$tableName}");

            DB::statement("
                CREATE TABLE {$tableName} AS
                SELECT * FROM activity_logs
                WHERE 1=0
            ");

            // Add indexes for archive table
            DB::statement("CREATE INDEX ON {$tableName} (created_at)");
            DB::statement("CREATE INDEX ON {$tableName} (user_id)");
            DB::statement("CREATE INDEX ON {$tableName} (event)");

            $this->info("Archive table created with indexes.");
        }
    }
}
