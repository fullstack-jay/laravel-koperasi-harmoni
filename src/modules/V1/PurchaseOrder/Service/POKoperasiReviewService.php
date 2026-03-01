<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Service;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\V1\PurchaseOrder\Enums\POStatusEnum;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;
use Modules\V1\PurchaseOrder\Models\PurchaseOrderItem;

final class POKoperasiReviewService
{
    public function __construct(
        private POStatusService $statusService,
        private POCalculationService $calculationService
    ) {}

    /**
     * Koperasi approves the price change
     * Status: PERUBAHAN_HARGA → DIKONFIRMASI_SUPPLIER
     */
    public function approvePriceChange(string $poId, ?string $userId = null): PurchaseOrder
    {
        $po = PurchaseOrder::find($poId);

        if (!$po) {
            throw new Exception('Purchase Order not found');
        }

        if ($po->status !== POStatusEnum::PERUBAHAN_HARGA) {
            throw new Exception('PO must be in PERUBAHAN_HARGA status');
        }

        $this->statusService->transitionStatus(
            $po,
            POStatusEnum::DIKONFIRMASI_SUPPLIER,
            'Koperasi approved price change',
            $userId
        );

        return $po->fresh();
    }

    /**
     * Koperasi edits PO (add/remove/update items) and resends to supplier
     * Status: PERUBAHAN_HARGA → TERKIRIM (kirim ulang)
     */
    public function editAndResend(string $poId, array $data, ?string $userId = null): PurchaseOrder
    {
        DB::beginTransaction();

        try {
            $po = PurchaseOrder::with('items')->find($poId);

            if (!$po) {
                throw new Exception('Purchase Order not found');
            }

            if ($po->status !== POStatusEnum::PERUBAHAN_HARGA) {
                throw new Exception('PO must be in PERUBAHAN_HARGA status');
            }

            // Update PO notes if provided
            if (isset($data['notes'])) {
                $po->update(['notes' => $data['notes']]);
            }

            // Handle items
            if (isset($data['items']) && is_array($data['items'])) {
                $estimatedTotal = 0;

                foreach ($data['items'] as $itemData) {
                    // Check if item already exists
                    $existingItem = PurchaseOrderItem::where('purchase_order_id', $po->id)
                        ->where('item_id', $itemData['item_id'])
                        ->first();

                    if ($existingItem) {
                        // Update existing item
                        $existingItem->update([
                            'estimated_unit_price' => $itemData['estimated_unit_price'],
                            'estimated_qty' => $itemData['estimated_qty'],
                            'estimated_subtotal' => $this->calculationService->calculateItemSubtotal(
                                $itemData['estimated_unit_price'],
                                $itemData['estimated_qty']
                            ),
                            'notes' => $itemData['notes'] ?? null,
                            // Reset actual values because koperasi made changes
                            'actual_unit_price' => null,
                            'actual_qty' => null,
                            'actual_subtotal' => null,
                        ]);

                        $estimatedTotal += $existingItem->estimated_subtotal;
                    } else {
                        // Add new item
                        $newItem = PurchaseOrderItem::create([
                            'purchase_order_id' => $po->id,
                            'item_id' => $itemData['item_id'],
                            'estimated_unit_price' => $itemData['estimated_unit_price'],
                            'estimated_qty' => $itemData['estimated_qty'],
                            'estimated_subtotal' => $this->calculationService->calculateItemSubtotal(
                                $itemData['estimated_unit_price'],
                                $itemData['estimated_qty']
                            ),
                            'notes' => $itemData['notes'] ?? null,
                        ]);

                        $estimatedTotal += $newItem->estimated_subtotal;
                    }
                }

                // Remove items that are not in the new list (optional - based on requirements)
                if (isset($data['remove_items']) && is_array($data['remove_items'])) {
                    PurchaseOrderItem::where('purchase_order_id', $po->id)
                        ->whereIn('id', $data['remove_items'])
                        ->delete();
                }

                // Update estimated total
                $po->update(['estimated_total' => $estimatedTotal]);
            }

            // Transition status back to TERKIRIM (resend to supplier)
            $this->statusService->transitionStatus(
                $po,
                POStatusEnum::TERKIRIM,
                'Koperasi revised PO and resending to supplier',
                $userId
            );

            DB::commit();

            return $po->fresh()->load('items.stockItem');
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
