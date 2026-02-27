<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Controllers;

use Exception;
use Modules\V1\Stock\Resources\StockAlertResource;
use Modules\V1\Stock\Services\StockAlertService;
use Shared\Helpers\ResponseHelper;

final class StockAlertController
{
    public function __construct(
        private StockAlertService $alertService
    ) {
    }

    /**
     * @OA\Post(
     *      path="/stock/alerts/list",
     *      summary="Get stock alerts",
     *      description="Returns a list of unresolved stock alerts (low stock, expired, etc.)",
     *      tags={"Stock"},
     *
     *      @OA\RequestBody(
     *
     *          @OA\MediaType(
     *              mediaType="application/json",
     *
     *              @OA\Schema(
     *
     *                  @OA\Property(property="pageNumber", type="integer", example=1, description="Page number"),
     *                  @OA\Property(property="pageSize", type="integer", example=10, description="Items per page"),
     *                  @OA\Property(property="sortDir", type="string", enum={"ASC", "DESC"}, example="ASC", description="Sort direction"),
     *                  @OA\Property(property="sortDirColumn", type="string", example="id", description="Column to sort by"),
     *                  @OA\Property(property="search", type="string", example="LOW", description="Global search string")
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *              @OA\Property(property="message", type="string", example="Success")
     *          )
     *      ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function index()
    {
        try {
            $type = request()->query('type');
            $alerts = $this->alertService->getUnresolvedAlerts($type);

            return ResponseHelper::success(
                data: StockAlertResource::collection($alerts)
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                message: 'Failed to retrieve alerts',
                exception: $e
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/stock/alerts/resolve/{id}",
     *     summary="Resolve stock alert",
     *     description="Mark a stock alert as resolved",
     *     tags={"Stock"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Alert UUID",
     *
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Alert resolved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Alert resolved successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Alert not found"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function resolve(string $id)
    {
        try {
            $result = $this->alertService->resolveAlert($id);

            if (! $result) {
                return ResponseHelper::notFound('Alert not found');
            }

            return ResponseHelper::success(
                message: 'Alert resolved successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                message: 'Failed to resolve alert',
                exception: $e
            );
        }
    }
}
