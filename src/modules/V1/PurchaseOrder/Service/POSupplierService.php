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

            // Reload items to get updated actual_unit_price values
            $po->load('items');

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
                // UPDATE: Update harga beli di master supplier items
                $this->updateSupplierItemPrices($po->id, $items);

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

    /**
     * Update buy_price and price_updated_at in supplier_items master table
     * and update buy_price and last_price_update_at in stock_items master table
     * when supplier confirms PO with price changes
     */
    private function updateSupplierItemPrices(string $poId, array $itemsData): void
    {
        $po = PurchaseOrder::find($poId);

        if (!$po) {
            return;
        }

        // Get all PO items
        $poItems = PurchaseOrderItem::where('purchase_order_id', $poId)->get();

        foreach ($poItems as $poItem) {
            // Find the corresponding item data from request
            $itemData = collect($itemsData)->firstWhere('itemId', $poItem->item_id);

            if (!$itemData) {
                continue;
            }

            // Find stock item to get its code
            $stockItem = \Modules\V1\Stock\Models\StockItem::find($poItem->item_id);

            if (!$stockItem) {
                continue;
            }

            $newPrice = (float) $itemData['actualPrice'];

            // Update supplier_items master table
            \Modules\V1\Supplier\Models\SupplierItem::where('supplier_id', $po->supplier_id)
                ->where('code', $stockItem->code)
                ->update([
                    'buy_price' => $newPrice,
                    'price_updated_at' => now(),
                ]);

            // Update stock_items master table with new price and timestamp
            $stockItem->update([
                'buy_price' => $newPrice,
                'last_price_update_at' => now(),
            ]);
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
                'cancellation_reason' => $cancellationReason,
                'cancelled_items' => $cancelledItems,
            ]);

            $this->statusService->transitionStatus(
                $po,
                POStatusEnum::DIBATALKAN_DRAFT,
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

    public function cancelPO(string $poId, array $cancelItems, ?string $message = null, ?string $userId = null): PurchaseOrder
    {
        DB::beginTransaction();

        try {
            $po = PurchaseOrder::with('items')->find($poId);

            if (!$po) {
                throw new Exception('Purchase Order not found');
            }

            if ($po->status !== POStatusEnum::TERKIRIM) {
                throw new Exception('PO must be in TERKIRIM status to be cancelled');
            }

            // Validate cancelled items belong to this PO
            $poItemIds = PurchaseOrderItem::where('purchase_order_id', $po->id)
                ->pluck('item_id')
                ->toArray();

            foreach ($cancelItems as $item) {
                if (!in_array($item['itemId'], $poItemIds)) {
                    throw new Exception('Item ' . $item['itemId'] . ' does not belong to this PO');
                }
            }

            // Build detailed cancellation message
            $detailedMessage = $message ?? $this->buildCancellationMessage($po->po_number, $cancelItems);

            // Extract just the item IDs for storage
            $cancelledItemIds = array_column($cancelItems, 'itemId');

            // Update PO with cancellation details
            $po->update([
                'cancellation_reason' => $detailedMessage,
                'cancelled_items' => $cancelItems,
            ]);

            $this->statusService->transitionStatus(
                $po,
                POStatusEnum::DIBATALKAN_DRAFT,
                $detailedMessage,
                $userId
            );

            DB::commit();

            return $po->fresh()->load('items');
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Build detailed cancellation message from cancel items
     */
    private function buildCancellationMessage(string $poNumber, array $cancelItems): string
    {
        $message = "Mohon maaf, untuk {$poNumber} berikut item yang tidak dapat dipenuhi:\n\n";

        foreach ($cancelItems as $item) {
            $message .= "• {$item['itemName']} (Qty: {$item['estimatedQty']} {$item['unit']})\n";
            $message .= "  Alasan: {$this->formatReason($item['reason'], $item)}\n";
        }

        return $message;
    }

    /**
     * Format reason code to human readable text
     */
    private function formatReason(string $reasonCode, array $item): string
    {
        return match($reasonCode) {
            'STOK_TERSISA' => "Stok tersisa {$item['quantity']} {$item['unit']}",
            'STOK_HABIS' => "Stok habis",
            default => $reasonCode,
        };
    }

    /**
     * Get quantity from item (handle null values)
     */
    private function getQuantity(array $item): int
    {
        return $item['quantity'] ?? 0;
    }
}
