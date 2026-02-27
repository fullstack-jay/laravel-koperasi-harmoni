<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Controllers;

use Exception;
use Illuminate\Http\Request;
use Modules\V1\Stock\Requests\CreateStockItemRequest;
use Modules\V1\Stock\Requests\UpdateStockItemRequest;
use Modules\V1\Stock\Resources\StockItemResource;
use Modules\V1\Stock\Services\StockService;
use Shared\Helpers\ResponseHelper;

final class StockItemController
{
    public function __construct(
        private StockService $stockService
    ) {
    }

    /**
     * @OA\Post(
     *      path="/stock/items/list",
     *      summary="Get all stock items",
     *      description="Returns a paginated list of all stock items.",
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
     *                  @OA\Property(property="search", type="string", example="Beras", description="Global search string")
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
    public function index(Request $request)
    {
        try {
            $pageNumber = $request->input('pageNumber', 1);
            $pageSize = $request->input('pageSize', 15);
            $sortDirColumn = $request->input('sortDirColumn', 'created_at');
            $sortDir = $request->input('sortDir', 'desc');
            $search = $request->input('search', '');

            $items = $this->stockService->getAllItems(
                pageNumber: $pageNumber,
                pageSize: $pageSize,
                sortColumn: $sortDirColumn,
                sortDir: $sortDir,
                search: $search
            );

            return ResponseHelper::success(
                data: StockItemResource::collection($items)
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                message: 'Failed to retrieve stock items',
                exception: $e
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/stock/items/create",
     *     summary="Create a new stock item",
     *     description="Create a new stock item with pricing and stock information",
     *     tags={"Stock"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 required={"code", "name", "category", "unit", "min_stock", "buy_price", "sell_price"},
     *
     *                 @OA\Property(property="code", type="string", example="STK-001"),
     *                 @OA\Property(property="name", type="string", example="Beras Premium 25kg"),
     *                 @OA\Property(property="category", type="string", example="Beras"),
     *                 @OA\Property(property="unit", type="string", example="karung"),
     *                 @OA\Property(property="min_stock", type="integer", example=50, description="Minimum stock level for alerts"),
     *                 @OA\Property(property="buy_price", type="number", format="float", example=150000),
     *                 @OA\Property(property="sell_price", type="number", format="float", example=165000)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Stock item created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Stock item created successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function store(CreateStockItemRequest $request)
    {
        try {
            $item = $this->stockService->createItem($request->validated());

            return ResponseHelper::success(
                data: new StockItemResource($item),
                message: 'Stock item created successfully',
                status: 201
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                message: 'Failed to create stock item',
                exception: $e
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/stock/items/detail/{id}",
     *     summary="Get stock item detail",
     *     description="Get detailed information about a specific stock item",
     *     tags={"Stock"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Stock Item UUID",
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
     *         description="Stock item not found"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function show(string $id)
    {
        try {
            $item = $this->stockService->findItem($id);

            return ResponseHelper::success(
                data: new StockItemResource($item)
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                message: $e->getMessage(),
                status: $e->getCode() === 404 ? 404 : 500
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/stock/items/update/{id}",
     *     summary="Update stock item",
     *     description="Update an existing stock item's information",
     *     tags={"Stock"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Stock Item UUID",
     *
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="code", type="string", example="STK-001"),
     *                 @OA\Property(property="name", type="string", example="Beras Premium 25kg"),
     *                 @OA\Property(property="category", type="string", example="Beras"),
     *                 @OA\Property(property="unit", type="string", example="karung"),
     *                 @OA\Property(property="min_stock", type="integer", example=50),
     *                 @OA\Property(property="buy_price", type="number", format="float", example=150000),
     *                 @OA\Property(property="sell_price", type="number", format="float", example=165000)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Stock item updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Stock item updated successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Stock item not found"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function update(UpdateStockItemRequest $request, string $id)
    {
        try {
            $item = $this->stockService->updateItem($id, $request->validated());

            return ResponseHelper::success(
                data: new StockItemResource($item),
                message: 'Stock item updated successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                message: $e->getMessage() ?? 'Failed to update stock item',
                status: $e->getCode() === 404 ? 404 : 500
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/stock/items/delete/{id}",
     *     summary="Delete stock item",
     *     description="Delete a stock item",
     *     tags={"Stock"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Stock Item UUID",
     *
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Stock item deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Stock item deleted successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Stock item not found"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function destroy(string $id)
    {
        try {
            $this->stockService->deleteItem($id);

            return ResponseHelper::success(
                message: 'Stock item deleted successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                message: $e->getMessage() ?? 'Failed to delete stock item',
                status: $e->getCode() === 404 ? 404 : 500
            );
        }
    }
}
