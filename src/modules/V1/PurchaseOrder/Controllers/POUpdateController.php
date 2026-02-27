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
     *     path="/purchase-orders/{po}/update",
     *     summary="Update purchase order",
     *     description="Update a draft purchase order",
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
     *                 @OA\Property(property="supplier_id", type="string", format="uuid"),
     *                 @OA\Property(property="po_date", type="string", format="date"),
     *                 @OA\Property(property="estimated_delivery_date", type="string", format="date"),
     *                 @OA\Property(property="items", type="array", @OA\Items(
     *                     type="object",
     *                     required={"item_id", "estimated_qty", "estimated_unit_price"},
     *                     @OA\Property(property="item_id", type="string", format="uuid"),
     *                     @OA\Property(property="estimated_qty", type="integer"),
     *                     @OA\Property(property="estimated_unit_price", type="number", format="float")
     *                 )),
     *                 @OA\Property(property="notes", type="string")
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
     *         description="Can only update draft POs"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function update(Request $request, PurchaseOrder $po)
    {
        try {
            if ($po->status !== POStatusEnum::DRAFT) {
                return ResponseHelper::error('Can only update draft POs', 400);
            }

            $request->merge(['updated_by' => $request->user()?->id]);

            $po->update($request->only([
                'po_date',
                'supplier_id',
                'estimated_delivery_date',
                'notes',
            ]));

            if ($request->has('items')) {
                // Delete existing items
                PurchaseOrderItem::where('purchase_order_id', $po->id)->delete();

                // Create new items
                foreach ($request->items as $item) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'item_id' => $item['item_id'],
                        'estimated_unit_price' => $item['estimated_unit_price'],
                        'estimated_qty' => $item['estimated_qty'],
                        'estimated_subtotal' => $this->calculationService->calculateItemSubtotal(
                            $item['estimated_unit_price'],
                            $item['estimated_qty']
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
