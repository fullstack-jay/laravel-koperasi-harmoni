<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Controllers;

use Exception;
use Modules\V1\Stock\Resources\StockBatchResource;
use Modules\V1\Stock\Services\BatchManagementService;
use Shared\Helpers\ResponseHelper;

final class StockBatchController
{
    public function __construct(
        private BatchManagementService $batchService
    ) {
    }

    /**
     * @OA\Post(
     *      path="/stock/batches/list/{itemId}",
     *      summary="Get batches by item",
     *      description="Returns a list of stock batches for a specific item, sorted by expiry date (FEFO)",
     *      tags={"Stock"},
     *
     *      @OA\Parameter(
     *          name="itemId",
     *          in="path",
     *          required=true,
     *          description="Stock Item UUID",
     *
     *          @OA\Schema(type="string", format="uuid")
     *      ),
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
     *                  @OA\Property(property="search", type="string", example="BATCH", description="Global search string")
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
    public function index(string $itemId)
    {
        try {
            $status = request()->query('status');
            $batches = $this->batchService->getBatchesByItem($itemId, $status);

            return ResponseHelper::success(
                data: StockBatchResource::collection($batches)
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                message: 'Failed to retrieve batches',
                exception: $e
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/stock/batches/detail/{id}",
     *     summary="Get batch detail",
     *     description="Get detailed information about a specific stock batch",
     *     tags={"Stock"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Stock Batch UUID",
     *
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Success")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Batch not found"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function show(string $id)
    {
        try {
            $batch = $this->batchService->getBatch($id);

            return ResponseHelper::success(
                data: new StockBatchResource($batch)
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                message: $e->getMessage(),
                status: $e->getCode() === 404 ? 404 : 500
            );
        }
    }
}
