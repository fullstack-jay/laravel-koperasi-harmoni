<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Controllers;

use Exception;
use Illuminate\Http\Request;
use Modules\V1\PurchaseOrder\Enums\POStatusEnum;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;
use Modules\V1\PurchaseOrder\Resources\POResource;
use Modules\V1\PurchaseOrder\Service\POService;
use Shared\Helpers\ResponseHelper;

final class POController extends POBaseController
{
    public function __construct(
        private POService $poService
    ) {
    }
    /**
     * @OA\Post(
     *      path="/PurchaseOrders/LoadData",
     *      summary="Get all purchase orders",
     *      description="Returns a paginated list of purchase orders including all statuses (draft, dibatalkan_draft, terkirim, perubahan_harga, dikonfirmasi_supplier, dikonfirmasi_koperasi, selesai, dibatalkan_koperasi).",
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
     *                  @OA\Property(property="sortColumn", type="string", example="id", description="Column to sort by"),
     *                  @OA\Property(property="sortColumnDir", type="string", enum={"ASC", "DESC"}, example="ASC", description="Sort direction"),
     *                  @OA\Property(property="search", type="string", example="PO", description="Global search string"),
     *                  @OA\Property(property="status", type="string", example="draft", description="Filter by status"),
     *                  @OA\Property(property="supplier_id", type="string", format="uuid", example="uuid", description="Filter by supplier ID")
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
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="data", type="array", @OA\Items(
     *                  type="object",
     *                  @OA\Property(property="id", type="string", format="uuid"),
     *                  @OA\Property(property="poNumber", type="string"),
     *                  @OA\Property(property="status", type="string", enum={"draft", "dibatalkan_draft", "terkirim", "perubahan_harga", "dikonfirmasi_supplier", "dikonfirmasi_koperasi", "selesai", "dibatalkan_koperasi"}),
     *                  @OA\Property(property="statusLabel", type="string", example="Draft (Dibatalkan)", description="Human readable status label"),
     *                  @OA\Property(property="cancellationReason", type="string", nullable=true, example="Budget tidak tersedia")
     *              ))
     *          )
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
        $sortColumn = $request->input('sortColumn', 'created_at');
        $sortColumnDir = $request->input('sortColumnDir', 'desc');
        $search = $request->input('search', '');

        $query = PurchaseOrder::with(['supplier', 'items.stockItem.expiryBatches']);

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
        $pos = $query->orderBy($sortColumn, $sortColumnDir)
                      ->offset($offset)
                      ->limit($pageSize)
                      ->get();

        return ResponseHelper::success(POResource::collection($pos));
    }

    /**
     * @OA\Post(
     *     path="/PurchaseOrders/{po}",
     *     summary="Get purchase order detail",
     *     description="Get detailed information about a specific purchase order. For cancelled draft POs (status: dibatalkan_draft), statusLabel will show 'Draft (Dibatalkan)'",
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
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="poNumber", type="string", example="PO-20260302-0001"),
     *                 @OA\Property(property="status", type="string", example="dibatalkan_draft"),
     *                 @OA\Property(property="statusLabel", type="string", example="Draft (Dibatalkan)", description="Shows 'Draft (Dibatalkan)' for dibatalkan_draft status"),
     *                 @OA\Property(property="cancellationReason", type="string", nullable=true, example="Budget tidak tersedia"),
     *                 @OA\Property(property="estimatedTotal", type="number", format="float"),
     *                 @OA\Property(property="notes", type="string", nullable=true),
     *                 @OA\Property(property="items", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function show(PurchaseOrder $po)
    {
        $po->load(['supplier', 'items.stockItem.expiryBatches', 'statusHistories']);

        return ResponseHelper::success(new POResource($po));
    }

    /**
     * @OA\Post(
     *     path="/PurchaseOrders/{po}/Delete",
     *     summary="Hard delete purchase order",
     *     description="Permanently delete a purchase order from the database. This action cannot be undone. Only POs with DRAFT status can be deleted. Related records (items, status histories) will be cascade deleted automatically.",
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
     *         description="PO deleted successfully",
     *
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Purchase order deleted successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="PO not found"
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete PO in current status",
     *
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Cannot delete PO in 'terkirim' status. Only draft POs can be deleted.")
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function delete(PurchaseOrder $po)
    {
        try {
            $this->poService->hardDelete($po->id);

            return ResponseHelper::success(
                message: 'Purchase order deleted successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                $e->getMessage(),
                $e->getCode() === 422 ? 422 : 500
            );
        }
    }
}
