<?php

declare(strict_types=1);

namespace Modules\V1\Logging\Controllers;

use App\Http\Controllers\V1\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\V1\Logging\Model\ActivityLog;
use Modules\V1\Logging\Resources\ActivityLogResource;
use Modules\V1\User\Models\User;
use Shared\Helpers\ResponseHelper;

final class AdminActivityLogController extends Controller
{
    /**
     * @OA\Post(
     *      path="/Admin/Logs/Activities/LoadData",
     *      summary="Get all activity logs (List)",
     *      description="Retrieve paginated list of all admin activity logs with search and sorting capabilities",
     *      tags={"Activity Logs"},
     *      security={{"bearerAuth": {}}},
     *
     *      @OA\RequestBody(
     *          required=false,
     *          description="Optional parameters for filtering, pagination, and sorting",
     *
     *          @OA\JsonContent(
     *              @OA\Property(property="search", type="string", example="", description="Global search by event, description, or admin name"),
     *              @OA\Property(property="pageNumber", type="integer", example=1, description="Page number"),
     *              @OA\Property(property="pageSize", type="integer", example=15, description="Items per page"),
     *              @OA\Property(property="sortColumn", type="string", example="created_at", description="Column to sort by (id, event, description, created_at)"),
     *              @OA\Property(property="sortColumnDir", type="string", enum={"asc", "desc"}, example="desc", description="Sort direction (asc or desc)"),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="statusCode", type="integer", example=200),
     *              @OA\Property(property="data", type="array", @OA\Items(
     *                  type="object",
     *                  @OA\Property(property="no", type="integer", example=1, description="Nomor urut"),
     *                  @OA\Property(property="id", type="string", format="uuid"),
     *                  @OA\Property(property="event", type="string", example="login"),
     *                  @OA\Property(property="description", type="string", example="User logged in"),
     *                  @OA\Property(property="severity", type="string", enum={"info", "warning", "error", "success"}, example="success"),
     *                  @OA\Property(property="severityColor", type="string", example="#10B981"),
     *                  @OA\Property(property="severityIcon", type="string", example="✅"),
     *                  @OA\Property(property="severityLabel", type="string", example="Success"),
     *                  @OA\Property(property="admin", ref="#/components/schemas/AdminResource"),
     *                  @OA\Property(property="createdAt", type="string", example="2025-01-15 10:30:00"),
     *              )),
     *              @OA\Property(property="meta", type="object",
     *                  @OA\Property(property="total", type="integer", example=100),
     *                  @OA\Property(property="page", type="integer", example=1),
     *                  @OA\Property(property="pageSize", type="integer", example=15),
     *                  @OA\Property(property="totalPages", type="integer", example=7),
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(response=401, ref="#/components/responses/401"),
     * )
     */
    public function activityLogs(Request $request): JsonResponse
    {
        $search = $request->input('search', '');
        $pageSize = $request->input('pageSize', 15);
        $pageNumber = $request->input('pageNumber', 1);
        $sortColumn = $request->input('sortColumn', 'created_at');
        $sortColumnDir = $request->input('sortColumnDir', 'desc');

        $query = ActivityLog::with(['user', 'subject']);

        // Global search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('event', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%")
                  ->orWhere('created_at', 'ILIKE', "%{$search}%")
                  ->orWhereHas('user', function ($userQ) use ($search) {
                      $userQ->where('first_name', 'ILIKE', "%{$search}%")
                            ->orWhere('last_name', 'ILIKE', "%{$search}%")
                            ->orWhere('email', 'ILIKE', "%{$search}%");
                  });
            });
        }

        // Apply sorting
        $allowedSortColumns = ['id', 'event', 'description', 'created_at', 'updated_at'];
        if (in_array($sortColumn, $allowedSortColumns)) {
            $direction = strtolower($sortColumnDir) === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sortColumn, $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $logs = $query->paginate($pageSize, ['*'], 'page', $pageNumber);

        // Add row number - reversed for desc sorting
        $items = $logs->items();
        $totalItems = count($items);
        $offset = ($pageNumber - 1) * $pageSize;

        // Check if sorting is descending
        $isDescending = strtolower($sortColumnDir) === 'desc' && in_array($sortColumn, ['id', 'event', 'description', 'created_at', 'updated_at']);

        $dataWithRowNumber = collect($items)->map(function ($item, $index) use ($offset, $isDescending, $totalItems) {
            $itemArray = $item->toArray();

            if ($isDescending) {
                // For desc: no 2, no 1 (terbalik)
                $itemArray = ['no' => $offset + $totalItems - $index] + $itemArray;
            } else {
                // For asc: no 1, no 2 (normal)
                $itemArray = ['no' => $offset + $index + 1] + $itemArray;
            }

            return $itemArray;
        });

        return ResponseHelper::success(
            data: $dataWithRowNumber,
            meta: [
                'total' => $logs->total(),
                'page' => $logs->currentPage(),
                'pageSize' => $logs->perPage(),
                'totalPages' => $logs->lastPage(),
            ]
        );
    }

    /**
     * @OA\Post(
     *      path="/Admin/Logs/Activities/View/{id}",
     *      summary="View single activity log",
     *      description="Retrieve detailed information about a specific activity log",
     *      tags={"Activity Logs"},
     *      security={{"bearerAuth": {}}},
     *
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="Activity Log ID",
     *          required=true,
     *          @OA\Schema(type="string", format="uuid")
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="statusCode", type="integer", example=200),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="string", format="uuid"),
     *                  @OA\Property(property="event", type="string", example="login"),
     *                  @OA\Property(property="description", type="string", example="User logged in"),
     *                  @OA\Property(property="severity", type="string", enum={"info", "warning", "error", "success"}, example="success"),
     *                  @OA\Property(property="severityColor", type="string", example="#10B981"),
     *                  @OA\Property(property="severityIcon", type="string", example="✅"),
     *                  @OA\Property(property="severityLabel", type="string", example="Success"),
     *                  @OA\Property(property="admin", ref="#/components/schemas/AdminResource"),
     *                  @OA\Property(property="properties", type="object", example={"old": {"name": "John"}, "new": {"name": "Jane"}}),
     *                  @OA\Property(property="createdAt", type="string", example="2025-01-15 10:30:00"),
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(response=401, ref="#/components/responses/401"),
     *      @OA\Response(response=404, ref="#/components/responses/404"),
     * )
     */
    public function view(string $id): JsonResponse
    {
        $log = ActivityLog::with(['user', 'subject'])->findOrFail($id);

        return ResponseHelper::success(data: ActivityLogResource::make($log));
    }

    /**
     * @OA\Post(
     *      path="/Admin/Logs/Activities/Dashboard",
     *      summary="Get activity dashboard statistics",
     *      description="Retrieve activity statistics for dashboard including recent activities, summary, top events, and most active users",
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
     *                  @OA\Property(property="recent_activities", type="array", @OA\Items(type="object")),
     *                  @OA\Property(property="activity_summary", type="object",
     *                      @OA\Property(property="today", type="integer", example=15),
     *                      @OA\Property(property="this_week", type="integer", example=120),
     *                      @OA\Property(property="this_month", type="integer", example=450),
     *                  ),
     *                  @OA\Property(property="top_events", type="array", @OA\Items(type="object")),
     *                  @OA\Property(property="most_active_users", type="array", @OA\Items(type="object")),
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(response=401, ref="#/components/responses/401"),
     * )
     */
    public function activityDashboard(): JsonResponse
    {
        $data = [
            'recent_activities' => ActivityLog::with(['user', 'subject'])
                ->latest()
                ->limit(20)
                ->get(),

            'activity_summary' => [
                'today' => ActivityLog::whereDate('created_at', today())->count(),
                'this_week' => ActivityLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'this_month' => ActivityLog::whereMonth('created_at', now()->month)->count(),
            ],

            'top_events' => ActivityLog::selectRaw('event, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('event')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),

            'most_active_users' => ActivityLog::selectRaw('user_id, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(30))
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->orderByDesc('count')
                ->with('user')
                ->limit(10)
                ->get(),
        ];

        return ResponseHelper::success(data: $data);
    }
}
