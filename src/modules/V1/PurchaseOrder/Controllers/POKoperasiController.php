<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Controllers;

use Exception;
use Illuminate\Http\Request;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;
use Modules\V1\PurchaseOrder\Resources\POResource;
use Modules\V1\PurchaseOrder\Service\POKoperasiService;
use Modules\V1\PurchaseOrder\Service\POService;
use Shared\Helpers\ResponseHelper;

final class POKoperasiController extends POBaseController
{
    public function __construct(
        private POKoperasiService $koperasiService,
        private POService $poService
    ) {}

    /**
     * @OA\Post(
     *     path="/purchase-orders/{po}/koperasi/confirm",
     *     summary="Koperasi confirms supplier response",
     *     description="Koperasi confirms supplier's price/quantity confirmation",
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
     *         description="Supplier response confirmed successfully"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function confirmSupplierResponse(Request $request, PurchaseOrder $po)
    {
        try {
            $result = $this->koperasiService->confirmSupplierResponse(
                $po->id,
                $request->user()?->id
            );

            return ResponseHelper::success(
                data: new POResource($result),
                message: 'Supplier response confirmed successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/purchase-orders/{po}/koperasi/reject",
     *     summary="Koperasi rejects supplier response",
     *     description="Koperasi rejects supplier's price/quantity confirmation",
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
     *                 @OA\Property(property="reason", type="string")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Supplier response rejected successfully"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function rejectSupplierResponse(Request $request, PurchaseOrder $po)
    {
        try {
            $result = $this->koperasiService->rejectSupplierResponse(
                $po->id,
                $request->reason,
                $request->user()?->id
            );

            return ResponseHelper::success(
                data: new POResource($result),
                message: 'Supplier response rejected successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/purchase-orders/{po}/koperasi/receive",
     *     summary="Receive goods from PO",
     *     description="Receive goods from supplier, create stock batches, and record purchase transaction",
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
     *                     required={"item_id", "received_qty", "actual_unit_price", "expiry_date"},
     *                     @OA\Property(property="item_id", type="string", format="uuid"),
     *                     @OA\Property(property="received_qty", type="integer"),
     *                     @OA\Property(property="actual_unit_price", type="number", format="float"),
     *                     @OA\Property(property="expiry_date", type="string", format="date", example="2025-12-31"),
     *                     @OA\Property(property="batch_number", type="string", description="Auto-generated if not provided")
     *                 ))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Goods received successfully, batches created"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function receiveGoods(Request $request, PurchaseOrder $po)
    {
        try {
            $request->merge(['received_by' => $request->user()?->id]);

            $result = $this->koperasiService->receiveGoods($po->id, $request->all());

            return ResponseHelper::success(
                data: [
                    'po' => new POResource($result['po']),
                    'batch_number' => $result['batch_number'],
                ],
                message: 'Goods received successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
