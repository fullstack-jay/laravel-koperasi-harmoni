<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\V1\PurchaseOrder\Enums\POStatusEnum;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;
use Modules\V1\PurchaseOrder\Models\PurchaseOrderItem;
use Modules\V1\PurchaseOrder\Resources\POResource;
use Modules\V1\PurchaseOrder\Service\POCalculationService;
use Modules\V1\Stock\Models\StockItem;
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
        Log::info("[PO Update] Starting PO update", [
            'po_id' => $po->id,
            'po_number' => $po->po_number,
            'user_id' => $request->user()?->id
        ]);

        try {
            // Allow update for DRAFT and DIBATALKAN_DRAFT
            $allowedStatuses = [POStatusEnum::DRAFT, POStatusEnum::DIBATALKAN_DRAFT];

            if (!in_array($po->status, $allowedStatuses)) {
                Log::error("[PO Update] Invalid PO status for update", [
                    'po_id' => $po->id,
                    'po_number' => $po->po_number,
                    'current_status' => $po->status->value,
                    'allowed_statuses' => array_map(fn($s) => $s->value, $allowedStatuses)
                ]);

                return ResponseHelper::error('Can only update draft or cancelled draft POs', 400);
            }

            Log::info("[PO Update] PO status validated", [
                'po_id' => $po->id,
                'po_number' => $po->po_number,
                'status' => $po->status->value
            ]);

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

            Log::info("[PO Update] Request validated", [
                'po_id' => $po->id,
                'has_items' => $request->has('items'),
                'items_count' => $request->has('items') ? count($request->items) : 0
            ]);

            // Validate stock availability if items are provided
            if ($request->has('items')) {
                Log::info("[PO Update] Validating stock availability", [
                    'po_id' => $po->id,
                    'items_count' => count($request->items)
                ]);

                $this->validateStockAvailability($request->items, $po);

                Log::info("[PO Update] Stock availability validated successfully", [
                    'po_id' => $po->id
                ]);
            }

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

            Log::info("[PO Update] PO header updated", [
                'po_id' => $po->id,
                'po_number' => $po->po_number,
                'updated_fields' => array_keys($poData)
            ]);

            if ($request->has('items')) {
                // Delete existing items
                $deletedItems = PurchaseOrderItem::where('purchase_order_id', $po->id)->delete();

                Log::info("[PO Update] Deleted existing PO items", [
                    'po_id' => $po->id,
                    'deleted_count' => $deletedItems
                ]);

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

                    Log::info("[PO Update] Created PO item", [
                        'po_id' => $po->id,
                        'item_id' => $item['itemId'],
                        'estimated_qty' => $item['estimatedQty'],
                        'estimated_unit_price' => $item['estimatedUnitPrice']
                    ]);
                }

                // Recalculate total
                $this->calculationService->updatePOTotal($po);

                Log::info("[PO Update] PO total recalculated", [
                    'po_id' => $po->id,
                    'new_total' => $po->fresh()->estimated_total
                ]);
            }

            Log::info("[PO Update] PO updated successfully", [
                'po_id' => $po->id,
                'po_number' => $po->po_number,
                'total_items' => $request->has('items') ? count($request->items) : 0
            ]);

            return ResponseHelper::success(
                data: new POResource($po->load('items')),
                message: 'Purchase Order updated successfully'
            );
        } catch (Exception $e) {
            Log::error("[PO Update] Failed to update PO", [
                'po_id' => $po->id,
                'po_number' => $po->po_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Validate stock availability for items
     * Ensures the requested quantity does not exceed available stock
     */
    private function validateStockAvailability(array $items, PurchaseOrder $po): void
    {
        foreach ($items as $item) {
            $stockItem = StockItem::find($item['itemId']);

            if (!$stockItem) {
                Log::error("[PO Update] Stock item not found during validation", [
                    'po_id' => $po->id,
                    'po_number' => $po->po_number,
                    'item_id' => $item['itemId']
                ]);

                throw new Exception("Item dengan ID {$item['itemId']} tidak ditemukan");
            }

            $availableStock = $stockItem->current_stock ?? 0;
            $requestedQty = $item['estimatedQty'] ?? 0;

            Log::info("[PO Update] Checking stock availability", [
                'po_id' => $po->id,
                'item_id' => $item['itemId'],
                'item_name' => $stockItem->name,
                'requested_qty' => $requestedQty,
                'available_stock' => $availableStock
            ]);

            // Check if requested quantity exceeds available stock
            if ($requestedQty > $availableStock) {
                $errorMessage = "Mohon maaf, untuk {$po->po_number} berikut item yang tidak dapat dipenuhi:\n\n";
                $errorMessage .= "• {$stockItem->name} (Qty: {$requestedQty})\n";
                $errorMessage .= "  Alasan: Stok tersisa {$availableStock}";

                Log::error("[PO Update] Stock validation failed - insufficient stock", [
                    'po_id' => $po->id,
                    'po_number' => $po->po_number,
                    'item_id' => $item['itemId'],
                    'item_name' => $stockItem->name,
                    'requested_qty' => $requestedQty,
                    'available_stock' => $availableStock,
                    'shortage' => $requestedQty - $availableStock
                ]);

                throw new Exception($errorMessage);
            }

            Log::info("[PO Update] Stock availability validated for item", [
                'po_id' => $po->id,
                'item_id' => $item['itemId'],
                'item_name' => $stockItem->name,
                'requested_qty' => $requestedQty,
                'available_stock' => $availableStock,
                'status' => 'OK'
            ]);
        }
    }
}
