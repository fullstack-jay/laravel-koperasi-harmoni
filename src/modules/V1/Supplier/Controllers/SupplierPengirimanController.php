<?php

declare(strict_types=1);

namespace Modules\V1\Supplier\Controllers;

use Exception;
use Illuminate\Http\Request;
use Modules\V1\PurchaseOrder\Enums\POStatusEnum;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;
use Modules\V1\Supplier\Requests\PengirimanLoadDataRequest;
use Modules\V1\Supplier\Resources\PengirimanResource;
use Shared\Helpers\ResponseHelper;

final class SupplierPengirimanController
{
    /**
     * @OA\Post(
     *     path="/Suppliers/Pengiriman/LoadData",
     *     summary="Load POs for delivery/pengiriman",
     *     description="Get list of purchase orders ready for delivery. For supplier role: returns their own POs. For super_admin role: must provide supplierId parameter.",
     *     tags={"Suppliers"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 @OA\Property(property="pageNumber", type="integer", example=1),
     *                 @OA\Property(property="pageSize", type="integer", example=10),
     *                 @OA\Property(property="sortColumn", type="string", example="id"),
     *                 @OA\Property(property="sortColumnDir", type="string", enum={"ASC", "DESC"}, example="ASC"),
     *                 @OA\Property(property="search", type="string", example=""),
     *                 @OA\Property(property="supplierId", type="string", format="uuid", example="a12ee051-b01a-4e65-bc83-42594da0f757", description="Required for super_admin role")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="poNumber", type="string", example="PO-20260301-0002"),
     *                 @OA\Property(property="poDate", type="string", format="date", example="2026-03-01"),
     *                 @OA\Property(property="supplierId", type="string", format="uuid"),
     *                 @OA\Property(property="supplierName", type="string", example="PT Sumber Pangan Indonesia"),
     *                 @OA\Property(property="status", type="string", example="dikonfirmasi_supplier"),
     *                 @OA\Property(property="invoiceNumber", type="string", example="INV-2026-001", nullable=true),
     *                 @OA\Property(property="koperasiName", type="string", example="Koperasi Harmoni"),
     *                 @OA\Property(property="koperasiAddress", type="string", example="Jl. Contoh No. 123", nullable=true),
     *                 @OA\Property(property="items", type="array", @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="itemId", type="string", format="uuid"),
     *                     @OA\Property(property="itemName", type="string"),
     *                     @OA\Property(property="estimatedQty", type="integer"),
     *                     @OA\Property(property="receivedQty", type="integer"),
     *                     @OA\Property(property="estimatedPrice", type="number"),
     *                     @OA\Property(property="actualPrice", type="number"),
     *                     @OA\Property(property="unit", type="string"),
     *                     @OA\Property(property="notes", type="string", nullable=true)
     *                 )),
     *                 @OA\Property(property="estimatedTotal", type="number", format="float"),
     *                 @OA\Property(property="actualTotal", type="number", format="float"),
     *                 @OA\Property(property="calculatedTotal", type="number", format="float", description="Total calculated from actualTotal if > 0, otherwise from items (actualPrice * estimatedQty)"),
     *                 @OA\Property(property="estimatedDeliveryDate", type="string", format="date"),
     *                 @OA\Property(property="notes", type="string", nullable=true),
     *                 @OA\Property(property="createdAt", type="string", format="date-time"),
     *                 @OA\Property(property="updatedAt", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="meta", type="object", @OA\Property(property="total", type="integer"), @OA\Property(property="perPage", type="integer"), @OA\Property(property="currentPage", type="integer"))
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function loadData(Request $request, PengirimanLoadDataRequest $loadDataRequest)
    {
        try {
            $user = $request->user();
            $validated = $loadDataRequest->validated();

            // Determine supplier ID based on role
            // - Super admin: can pass supplierId in request
            // - Supplier role: must use their own supplier_id
            if ($user?->hasRole('super_admin')) {
                $supplierId = $validated['supplierId'] ?? null;

                if (!$supplierId) {
                    return ResponseHelper::error(
                        'supplierId is required for super_admin role',
                        422
                    );
                }
            } else {
                // For supplier role, use their own supplier_id
                $supplierId = $user?->supplier_id;

                if (!$supplierId) {
                    return ResponseHelper::error(
                        'User is not associated with any supplier',
                        403
                    );
                }
            }

            // Get pagination and search parameters with defaults
            $pageNumber = $validated['pageNumber'] ?? 1;
            $pageSize = $validated['pageSize'] ?? 10;
            $sortColumn = $validated['sortColumn'] ?? 'id';
            $sortColumnDir = $validated['sortColumnDir'] ?? 'ASC';
            $search = $validated['search'] ?? '';

            // Build query - get POs that are ready for delivery (DIKONFIRMASI_SUPPLIER status only)
            $query = PurchaseOrder::with(['items.stockItem', 'supplier', 'createdBy'])
                ->where('supplier_id', $supplierId)
                ->where('status', POStatusEnum::DIKONFIRMASI_SUPPLIER->value);

            // Apply search if provided
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('po_number', 'ilike', "%{$search}%")
                        ->orWhereHas('supplier', function ($subQ) use ($search) {
                            $subQ->where('name', 'ilike', "%{$search}%");
                        });
                });
            }

            // Apply sorting
            $query->orderBy($sortColumn, $sortColumnDir);

            // Paginate
            $po = $query->paginate(
                perPage: $pageSize,
                page: $pageNumber
            );

            return ResponseHelper::paginate(
                PengirimanResource::collection($po->items()),
                $po->total(),
                $po->perPage(),
                $po->currentPage()
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                'Failed to load purchase orders: ' . $e->getMessage()
            );
        }
    }
}
