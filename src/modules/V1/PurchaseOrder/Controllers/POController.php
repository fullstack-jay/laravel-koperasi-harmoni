<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Controllers;

use Illuminate\Http\Request;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;
use Modules\V1\PurchaseOrder\Resources\POResource;
use Shared\Helpers\ResponseHelper;

final class POController extends POBaseController
{
    /**
     * @OA\Post(
     *      path="/purchase-orders/list",
     *      summary="Get all purchase orders",
     *      description="Returns a paginated list of purchase orders",
     *      tags={"Purchase Orders"},
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
     *                  @OA\Property(property="search", type="string", example="PO", description="Global search string")
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function index(Request $request)
    {
        $pageNumber = $request->input('pageNumber', 1);
        $pageSize = $request->input('pageSize', 15);
        $sortDirColumn = $request->input('sortDirColumn', 'created_at');
        $sortDir = $request->input('sortDir', 'desc');
        $search = $request->input('search', '');

        $query = PurchaseOrder::with(['supplier', 'items']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Apply global search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'ILIKE', "%{$search}%");
            });
        }

        // Apply sorting and pagination
        $offset = ($pageNumber - 1) * $pageSize;
        $pos = $query->orderBy($sortDirColumn, $sortDir)
                      ->offset($offset)
                      ->limit($pageSize)
                      ->get();

        return ResponseHelper::success(POResource::collection($pos));
    }

    /**
     * @OA\Post(
     *     path="/purchase-orders/{po}",
     *     summary="Get purchase order detail",
     *     description="Get detailed information about a specific purchase order",
     *     tags={"Purchase Orders"},
     *
     *     @OA\Parameter(
     *         name="po",
     *         in="path",
     *         required=true,
     *         description="Purchase Order UUID",
     *
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function show(PurchaseOrder $po)
    {
        $po->load(['supplier', 'items', 'statusHistories']);

        return ResponseHelper::success(new POResource($po));
    }
}
