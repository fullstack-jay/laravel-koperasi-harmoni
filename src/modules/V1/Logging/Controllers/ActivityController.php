<?php

declare(strict_types=1);

namespace Modules\V1\Logging\Controllers;

use App\Http\Controllers\V1\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\V1\Logging\Model\ActivityLog;
use Shared\Helpers\ResponseHelper;

final class ActivityController extends Controller
{
    /**
     * @OA\Post(
     *      path="/Admin/Logs/Activities/Storage-Stats",
     *      summary="Get activity log storage statistics",
     *      description="Retrieve storage usage statistics for activity logs including table size, row count, retention info, and recommendations",
     *      tags={"Activity Logs"},
     *      security={{"bearerAuth": {}}},
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="statusCode", type="integer", example=200),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="total_records", type="integer", example=50000),
     *                  @OA\Property(property="table_size_mb", type="number", format="float", example=125.5),
     *                  @OA\Property(property="data_size_mb", type="number", format="float", example=80.3),
     *                  @OA\Property(property="indexes_size_mb", type="number", format="float", example=45.2),
     *                  @OA\Property(property="oldest_record", type="string", example="2024-01-15 10:30:00"),
     *                  @OA\Property(property="newest_record", type="string", example="2025-02-27 15:30:00"),
     *                  @OA\Property(property="records_last_30_days", type="integer", example=15000),
     *                  @OA\Property(property="records_last_7_days", type="integer", example=3500),
     *                  @OA\Property(property="avg_records_per_day", type="integer", example=500),
     *                  @OA\Property(property="projected_records_in_90_days", type="integer", example=45000),
     *                  @OA\Property(property="projected_size_in_90_days_mb", type="number", format="float", example=250.5),
     *                  @OA\Property(property="retention_policy", type="object",
     *                      @OA\Property(property="lama_hari_penyimpanan", type="integer", example=90),
     *                      @OA\Property(property="pembersihan_otomatis_diaktifkan", type="boolean", example=true),
     *                      @OA\Property(property="pembersihan_berikutnya", type="string", example="2025-03-02 02:00:00"),
     *                  ),
     *                  @OA\Property(property="recommendations", type="array", @OA\Items(type="string"), example={"Pertimbangkan untuk mengurangi masa penyimpanan dari 90 menjadi 30-60 hari"}),
     *                  @OA\Property(property="warnings", type="array", @OA\Items(type="string")),
     *                  @OA\Property(property="health_status", type="string", example="healthy", enum={"healthy", "warning"}),
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(response=401, ref="#/components/responses/401"),
     * )
     */
    public function getStorageStats(): JsonResponse
    {
        try {
            // Get table size statistics
            $stats = DB::select("
                SELECT
                    pg_total_relation_size('activity_logs') as total_size,
                    pg_relation_size('activity_logs') as table_size,
                    pg_indexes_size('activity_logs') as indexes_size
            ");

            $totalSizeBytes = $stats[0]->total_size ?? 0;
            $tableSizeBytes = $stats[0]->table_size ?? 0;
            $indexesSizeBytes = $stats[0]->indexes_size ?? 0;

            // Get record counts
            $totalRecords = ActivityLog::count();
            $recordsLast30Days = ActivityLog::where('created_at', '>=', now()->subDays(30))->count();
            $recordsLast7Days = ActivityLog::where('created_at', '>=', now()->subDays(7))->count();

            // Get oldest and newest records
            $oldestRecord = ActivityLog::oldest('created_at')->first(['created_at']);
            $newestRecord = ActivityLog::latest('created_at')->first(['created_at']);

            // Calculate average records per day
            $daysSinceOldest = 1;
            if ($oldestRecord && $oldestRecord->created_at->isPast()) {
                $daysSinceOldest = max(1, now()->diffInDays($oldestRecord->created_at));
            }
            $avgRecordsPerDay = $totalRecords / max($daysSinceOldest, 1);

            // Project size in 90 days (only if we have meaningful data)
            $projectedRecordsIn90Days = 0;
            $projectedSizeIn90Days = 0;

            if ($daysSinceOldest > 7) { // Only project if we have at least 7 days of data
                $projectedRecordsIn90Days = $avgRecordsPerDay * 90;
                $avgSizePerRecord = $totalSizeBytes / max($totalRecords, 1);
                $projectedSizeIn90Days = $avgSizePerRecord * $projectedRecordsIn90Days;
            }

            // Generate recommendations based on data
            $recommendations = [];
            $warnings = [];

            // Check if table is growing too fast
            if ($recordsLast30Days > 100000) {
                $warnings[] = 'Volume log tinggi: ' . number_format($recordsLast30Days) . ' records dalam 30 hari terakhir. Pertimbangkan untuk mengurangi masa penyimpanan.';
            }

            // Check if table size is large
            if ($totalSizeBytes > 1024 * 1024 * 1024) { // > 1GB
                $warnings[] = 'Ukuran tabel besar: ' . round($totalSizeBytes / 1024 / 1024 / 1024, 2) . ' GB. Pertimbangkan untuk menjalankan pembersihan atau pengarsipan.';
            }

            // Check if retention policy needs adjustment
            $retentionDays = config('logging.lama_hari_penyimpanan', 90);
            if ($recordsLast30Days > 50000 && $retentionDays > 60) {
                $recommendations[] = 'Pertimbangkan untuk mengurangi masa penyimpanan dari ' . $retentionDays . ' menjadi 30-60 hari untuk mengontrol ukuran tabel.';
            }

            // Suggest cleanup if needed
            if ($totalRecords > 100000 && !$warnings) {
                $recommendations[] = 'Tabel memiliki ' . number_format($totalRecords) . ' records. Pertimbangkan untuk menjalankan perintah pembersihan: php artisan activity-logs:cleanup --force';
            }

            $data = [
                'total_records' => $totalRecords,
                'table_size_mb' => round($totalSizeBytes / 1024 / 1024, 2),
                'data_size_mb' => round($tableSizeBytes / 1024 / 1024, 2),
                'indexes_size_mb' => round($indexesSizeBytes / 1024 / 1024, 2),
                'oldest_record' => $oldestRecord?->created_at?->format('Y-m-d H:i:s'),
                'newest_record' => $newestRecord?->created_at?->format('Y-m-d H:i:s'),
                'records_last_30_days' => $recordsLast30Days,
                'records_last_7_days' => $recordsLast7Days,
                'avg_records_per_day' => (int) round($avgRecordsPerDay),
                'projected_records_in_90_days' => (int) round($projectedRecordsIn90Days),
                'projected_size_in_90_days_mb' => round($projectedSizeIn90Days / 1024 / 1024, 2),
                'retention_policy' => [
                    'lama_hari_penyimpanan' => $retentionDays,
                    'pembersihan_otomatis_diaktifkan' => config('logging.pembersihan_otomatis', true),
                    'pembersihan_berikutnya' => now()->next('Sunday')->setHour(2)->setMinute(0)->format('Y-m-d H:i:s'),
                ],
                'recommendations' => $recommendations,
                'warnings' => $warnings,
                'health_status' => empty($warnings) ? 'healthy' : 'warning',
            ];

            return ResponseHelper::success(data: $data);
        } catch (\Exception $e) {
            return ResponseHelper::error(
                message: 'Failed to retrieve storage stats: ' . $e->getMessage(),
                statusCode: 500
            );
        }
    }
}
