<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Controllers;

use Exception;
use Illuminate\Http\Request;
use Modules\V1\PurchaseOrder\Enums\POStatusEnum;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;
use Modules\V1\PurchaseOrder\Models\PurchaseOrderItem;
use Modules\V1\PurchaseOrder\Resources\POResource;
use Modules\V1\PurchaseOrder\Service\POCalculationService;
use Shared\Helpers\ResponseHelper;

final class POUpdateController extends POBaseController
{
    public function __construct(
        private POCalculationService $calculationService
    ) {}

    /**
     * @OA\Post(
     *     path="/PurchaseOrders/Update/{po}",
     *     summary="Update purchase order",
     *     description="Update a draft or cancelled draft purchase order. Only POs with DRAFT or DIBATALKAN_DRAFT status can be updated.",
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
     *
     *                 @OA\Property(property="poDate", type="string", format="date", example="2026-03-02"),
     *                 @OA\Property(property="supplierId", type="string", format="uuid"),
     *                 @OA\Property(property="estimatedDeliveryDate", type="string", format="date", example="2026-03-10"),
     *                 @OA\Property(property="notes", type="string"),
     *                 @OA\Property(property="items", type="array", @OA\Items(
     *                     type="object",
     *                     required={"itemId", "estimatedQty", "estimatedUnitPrice"},
     *                     @OA\Property(property="itemId", type="string", format="uuid", example="uuid-stock-item"),
     *                     @OA\Property(property="estimatedUnitPrice", type="number", format="float", example=50000),
     *                     @OA\Property(property="estimatedQty", type="integer", example=10),
     *                     @OA\Property(property="notes", type="string")
     *                 ))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="PO updated successfully"
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Can only update draft or cancelled draft POs"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function update(Request $request, PurchaseOrder $po)
    {
        try {
            // Allow update for DRAFT and DIBATALKAN_DRAFT
            $allowedStatuses = [POStatusEnum::DRAFT, POStatusEnum::DIBATALKAN_DRAFT];

            if (!in_array($po->status, $allowedStatuses)) {
                return ResponseHelper::error('Can only update draft or cancelled draft POs', 400);
            }

            // Validate request with camelCase fields
            $validated = $request->validate([
                'poDate' => 'sometimes|date',
                'supplierId' => 'sometimes|uuid|exists:suppliers,id',
                'estimatedDeliveryDate' => 'sometimes|date',
                'notes' => 'nullable|string|max:500',
                'items' => 'sometimes|array',
                'items.*.itemId' => 'required_with:items|uuid|exists:stock_items,id',
                'items.*.estimatedUnitPrice' => 'required_with:items|numeric|min:0',
                'items.*.estimatedQty' => 'required_with:items|integer|min:1',
                'items.*.notes' => 'nullable|string|max:500',
            ]);

            $request->merge(['updated_by' => $request->user()?->id]);

            // Map camelCase to snake_case for database
            $poData = [
                'po_date' => $request->poDate,
                'supplier_id' => $request->supplierId,
                'estimated_delivery_date' => $request->estimatedDeliveryDate,
                'notes' => $request->notes,
            ];

            // Remove null values
            $poData = array_filter($poData, fn($value) => !is_null($value));

            $po->update($poData);

            if ($request->has('items')) {
                // Delete existing items
                PurchaseOrderItem::where('purchase_order_id', $po->id)->delete();

                // Create new items
                foreach ($request->items as $item) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'item_id' => $item['itemId'],
                        'estimated_unit_price' => $item['estimatedUnitPrice'],
                        'estimated_qty' => $item['estimatedQty'],
                        'estimated_subtotal' => $this->calculationService->calculateItemSubtotal(
                            $item['estimatedUnitPrice'],
                            $item['estimatedQty']
                        ),
                        'notes' => $item['notes'] ?? null,
                    ]);
                }

                // Recalculate total
                $this->calculationService->updatePOTotal($po);
            }

            return ResponseHelper::success(
                data: new POResource($po->load('items')),
                message: 'Purchase Order updated successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
