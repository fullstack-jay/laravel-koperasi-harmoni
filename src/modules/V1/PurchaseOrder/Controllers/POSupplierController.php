<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Controllers;

use Exception;
use Illuminate\Http\Request;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;
use Modules\V1\PurchaseOrder\Resources\POResource;
use Modules\V1\PurchaseOrder\Service\POSupplierService;
use Shared\Helpers\ResponseHelper;

final class POSupplierController extends POBaseController
{
    public function __construct(
        private POSupplierService $supplierService
    ) {}

    /**
     * @OA\Post(
     *     path="/purchase-orders/{po}/supplier/confirm",
     *     summary="Supplier confirms PO",
     *     description="Supplier confirms purchase order with final prices and quantities",
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
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 required={"items"},
     *
     *                 @OA\Property(property="items", type="array", @OA\Items(
     *                     type="object",
     *                     required={"item_id", "confirmed_qty", "confirmed_unit_price"},
     *                     @OA\Property(property="item_id", type="string", format="uuid"),
     *                     @OA\Property(property="confirmed_qty", type="integer"),
     *                     @OA\Property(property="confirmed_unit_price", type="number", format="float")
     *                 ))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="PO confirmed successfully"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function confirm(Request $request, PurchaseOrder $po)
    {
        try {
            $request->merge(['user_id' => $request->user()?->id]);

            $result = $this->supplierService->confirmPO(
                $po->id,
                $request->items,
                $request->user_id
            );

            return ResponseHelper::success(
                data: new POResource($result),
                message: 'Purchase Order confirmed successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/purchase-orders/{po}/supplier/reject",
     *     summary="Supplier rejects PO",
     *     description="Supplier rejects purchase order with reason",
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
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 required={"reason"},
     *
     *                 @OA\Property(property="reason", type="string", example="Items not available")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="PO rejected successfully"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function reject(Request $request, PurchaseOrder $po)
    {
        try {
            $result = $this->supplierService->rejectPO(
                $po->id,
                $request->reason,
                $request->user()?->id
            );

            return ResponseHelper::success(
                data: new POResource($result),
                message: 'Purchase Order rejected successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
