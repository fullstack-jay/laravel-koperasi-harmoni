<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Service;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\V1\PurchaseOrder\Enums\POStatusEnum;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;
use Modules\V1\PurchaseOrder\Models\PurchaseOrderItem;

final class POSupplierService
{
    public function __construct(
        private POStatusService $statusService,
        private POCalculationService $calculationService
    ) {}

    public function confirmPO(string $poId, array $items, ?string $invoiceNumber = null, ?string $userId = null): PurchaseOrder
    {
        DB::beginTransaction();

        try {
            $po = PurchaseOrder::with('items')->find($poId);

            if (!$po) {
                throw new Exception('Purchase Order not found');
            }

            if ($po->status !== POStatusEnum::TERKIRIM) {
                throw new Exception('PO must be in TERKIRIM status');
            }

            $hasPriceChanges = false;

            foreach ($items as $itemData) {
                // Find item by stock item_id (not PO item id)
                $item = PurchaseOrderItem::where('purchase_order_id', $po->id)
                    ->where('item_id', $itemData['itemId'])
                    ->first();

                if (!$item) {
                    throw new Exception('Item not found');
                }

                // Check if price changed
                if ((float) $itemData['actualPrice'] !== (float) $item->estimated_unit_price) {
                    $hasPriceChanges = true;
                }

                // Update actual price and calculate actual qty and subtotal
                // Use estimated_qty as actual_qty since supplier doesn't provide it
                $actualQty = $item->estimated_qty;

                $item->update([
                    'actual_unit_price' => $itemData['actualPrice'],
                    'actual_qty' => $actualQty,
                    'actual_subtotal' => $this->calculationService->calculateItemSubtotal(
                        $itemData['actualPrice'],
                        $actualQty
                    ),
                ]);
            }

            $actualTotal = $this->calculationService->calculateActualTotal(
                $po->items->toArray()
            );

            // Update PO with actual total and invoice number
            $po->update([
                'actual_total' => $actualTotal,
                'invoice_number' => $invoiceNumber,
            ]);

            // Transition status based on price changes
            if ($hasPriceChanges) {
                // Price changed: needs koperasi review
                $this->statusService->transitionStatus(
                    $po,
                    POStatusEnum::PERUBAHAN_HARGA,
                    'Supplier confirmed with price changes, awaiting koperasi review',
                    $userId
                );
            } else {
                // No price changes: direct confirmation
                $this->statusService->transitionStatus(
                    $po,
                    POStatusEnum::DIKONFIRMASI_SUPPLIER,
                    'Supplier confirmed PO',
                    $userId
                );
            }

            DB::commit();

            return $po->fresh()->load('items');
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function rejectPO(string $poId, string $cancellationReason, array $cancelledItems, ?string $userId = null): PurchaseOrder
    {
        DB::beginTransaction();

        try {
            $po = PurchaseOrder::find($poId);

            if (!$po) {
                throw new Exception('Purchase Order not found');
            }

            if ($po->status !== POStatusEnum::TERKIRIM) {
                throw new Exception('PO must be in TERKIRIM status');
            }

            // Validate cancelled items belong to this PO
            $poItemIds = PurchaseOrderItem::where('purchase_order_id', $po->id)
                ->pluck('item_id')
                ->toArray();

            foreach ($cancelledItems as $item) {
                if (!in_array($item['itemId'], $poItemIds)) {
                    throw new Exception('Item ' . $item['itemId'] . ' does not belong to this PO');
                }
            }

            // Update PO with cancellation details
            $po->update([
                'rejection_reason' => $cancellationReason,
                'cancellation_reason' => $cancellationReason,
                'cancelled_items' => $cancelledItems,
            ]);

            $this->statusService->transitionStatus(
                $po,
                POStatusEnum::DIBATALKAN,
                $cancellationReason,
                $userId
            );

            DB::commit();

            return $po->fresh()->load('items');
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
