<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Service;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\V1\PurchaseOrder\Enums\POStatusEnum;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;
use Modules\V1\PurchaseOrder\Models\PurchaseOrderItem;
use Modules\V1\Stock\Models\StockItem;

final class POSupplierService
{
    public function __construct(
        private POStatusService $statusService,
        private POCalculationService $calculationService
    ) {}

    public function confirmPO(string $poId, array $items, ?string $invoiceNumber = null, ?string $userId = null): PurchaseOrder
    {
        Log::info("[PO Supplier Confirm] Starting PO confirmation", [
            'po_id' => $poId,
            'user_id' => $userId,
            'invoice_number' => $invoiceNumber,
            'items_count' => count($items)
        ]);

        DB::beginTransaction();

        try {
            $po = PurchaseOrder::with('items')->find($poId);

            if (!$po) {
                Log::error("[PO Supplier Confirm] PO not found", ['po_id' => $poId]);
                throw new Exception('Purchase Order not found');
            }

            Log::info("[PO Supplier Confirm] PO found", [
                'po_number' => $po->po_number,
                'current_status' => $po->status->value
            ]);

            if ($po->status !== POStatusEnum::TERKIRIM) {
                Log::error("[PO Supplier Confirm] Invalid PO status", [
                    'po_id' => $poId,
                    'po_number' => $po->po_number,
                    'current_status' => $po->status->value,
                    'expected_status' => POStatusEnum::TERKIRIM->value
                ]);
                throw new Exception('PO must be in TERKIRIM status');
            }

            $hasPriceChanges = false;
            $priceChangeDetails = [];

            foreach ($items as $itemData) {
                // Find item by stock item_id (not PO item id)
                $item = PurchaseOrderItem::where('purchase_order_id', $po->id)
                    ->where('item_id', $itemData['itemId'])
                    ->first();

                if (!$item) {
                    Log::error("[PO Supplier Confirm] PO Item not found", [
                        'po_id' => $poId,
                        'item_id' => $itemData['itemId']
                    ]);
                    throw new Exception('Item not found');
                }

                // Check if price changed
                $oldPrice = (float) $item->estimated_unit_price;
                $newPrice = (float) $itemData['actualPrice'];

                if ($newPrice !== $oldPrice) {
                    $hasPriceChanges = true;
                    $priceChangeDetails[] = [
                        'item_id' => $itemData['itemId'],
                        'old_price' => $oldPrice,
                        'new_price' => $newPrice,
                        'difference' => $newPrice - $oldPrice
                    ];

                    Log::info("[PO Supplier Confirm] Price changed for item", [
                        'po_id' => $poId,
                        'item_id' => $itemData['itemId'],
                        'old_price' => $oldPrice,
                        'new_price' => $newPrice
                    ]);
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

            Log::info("[PO Supplier Confirm] PO totals updated", [
                'po_id' => $poId,
                'po_number' => $po->po_number,
                'estimated_total' => $po->estimated_total,
                'actual_total' => $actualTotal,
                'has_price_changes' => $hasPriceChanges
            ]);

            // Transition status based on price changes
            if ($hasPriceChanges) {
                // Price changed: needs koperasi review
                // UPDATE: Update harga beli di master supplier items
                Log::info("[PO Supplier Confirm] Price changes detected, updating supplier prices", [
                    'po_id' => $poId,
                    'price_changes' => $priceChangeDetails
                ]);

                $this->updateSupplierItemPrices($po->id, $items);

                $this->statusService->transitionStatus(
                    $po,
                    POStatusEnum::PERUBAHAN_HARGA,
                    'Supplier confirmed with price changes, awaiting koperasi review',
                    $userId
                );

                Log::info("[PO Supplier Confirm] PO status changed to PERUBAHAN_HARGA", [
                    'po_id' => $poId,
                    'po_number' => $po->po_number
                ]);
            } else {
                // No price changes: direct confirmation
                $this->statusService->transitionStatus(
                    $po,
                    POStatusEnum::DIKONFIRMASI_SUPPLIER,
                    'Supplier confirmed PO',
                    $userId
                );

                Log::info("[PO Supplier Confirm] PO status changed to DIKONFIRMASI_SUPPLIER", [
                    'po_id' => $poId,
                    'po_number' => $po->po_number
                ]);
            }

            DB::commit();

            Log::info("[PO Supplier Confirm] PO confirmation completed successfully", [
                'po_id' => $poId,
                'po_number' => $po->po_number,
                'new_status' => $po->fresh()->status->value
            ]);

            return $po->fresh()->load('items');
        } catch (Exception $e) {
            DB::rollBack();

            Log::error("[PO Supplier Confirm] Failed to confirm PO", [
                'po_id' => $poId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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
        Log::info("[PO Supplier Reject] Starting PO rejection", [
            'po_id' => $poId,
            'user_id' => $userId,
            'cancelled_items_count' => count($cancelledItems)
        ]);

        DB::beginTransaction();

        try {
            $po = PurchaseOrder::find($poId);

            if (!$po) {
                Log::error("[PO Supplier Reject] PO not found", ['po_id' => $poId]);
                throw new Exception('Purchase Order not found');
            }

            Log::info("[PO Supplier Reject] PO found", [
                'po_number' => $po->po_number,
                'current_status' => $po->status->value
            ]);

            if ($po->status !== POStatusEnum::TERKIRIM) {
                Log::error("[PO Supplier Reject] Invalid PO status", [
                    'po_id' => $poId,
                    'po_number' => $po->po_number,
                    'current_status' => $po->status->value,
                    'expected_status' => POStatusEnum::TERKIRIM->value
                ]);
                throw new Exception('PO must be in TERKIRIM status');
            }

            // Validate cancelled items belong to this PO
            $poItemIds = PurchaseOrderItem::where('purchase_order_id', $po->id)
                ->pluck('item_id')
                ->toArray();

            foreach ($cancelledItems as $item) {
                if (!in_array($item['itemId'], $poItemIds)) {
                    Log::error("[PO Supplier Reject] Item does not belong to PO", [
                        'po_id' => $poId,
                        'item_id' => $item['itemId']
                    ]);
                    throw new Exception('Item ' . $item['itemId'] . ' does not belong to this PO');
                }
            }

            Log::info("[PO Supplier Reject] Updating current_stock for cancelled items", [
                'po_id' => $poId,
                'cancelled_items' => $cancelledItems
            ]);

            // Update current_stock for each cancelled item
            $this->updateCurrentStockFromCancelledItems($cancelledItems);

            // Update PO with cancellation details
            $po->update([
                'cancellation_reason' => $cancellationReason,
                'cancelled_items' => $cancelledItems,
            ]);

            Log::info("[PO Supplier Reject] Transitioning PO status to DIBATALKAN_DRAFT", [
                'po_id' => $poId,
                'po_number' => $po->po_number,
                'cancellation_reason' => $cancellationReason
            ]);

            $this->statusService->transitionStatus(
                $po,
                POStatusEnum::DIBATALKAN_DRAFT,
                $cancellationReason,
                $userId
            );

            DB::commit();

            Log::info("[PO Supplier Reject] PO rejection completed successfully", [
                'po_id' => $poId,
                'po_number' => $po->po_number,
                'new_status' => $po->fresh()->status->value
            ]);

            return $po->fresh()->load('items');
        } catch (Exception $e) {
            DB::rollBack();

            Log::error("[PO Supplier Reject] Failed to reject PO", [
                'po_id' => $poId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function cancelPO(string $poId, array $cancelItems, ?string $message = null, ?string $userId = null): PurchaseOrder
    {
        Log::info("[PO Supplier Cancel] Starting PO cancellation", [
            'po_id' => $poId,
            'user_id' => $userId,
            'cancel_items_count' => count($cancelItems)
        ]);

        DB::beginTransaction();

        try {
            $po = PurchaseOrder::with('items')->find($poId);

            if (!$po) {
                Log::error("[PO Supplier Cancel] PO not found", ['po_id' => $poId]);
                throw new Exception('Purchase Order not found');
            }

            Log::info("[PO Supplier Cancel] PO found", [
                'po_number' => $po->po_number,
                'current_status' => $po->status->value
            ]);

            if ($po->status !== POStatusEnum::TERKIRIM) {
                Log::error("[PO Supplier Cancel] Invalid PO status", [
                    'po_id' => $poId,
                    'po_number' => $po->po_number,
                    'current_status' => $po->status->value,
                    'expected_status' => POStatusEnum::TERKIRIM->value
                ]);
                throw new Exception('PO must be in TERKIRIM status to be cancelled');
            }

            // Validate cancelled items belong to this PO
            $poItemIds = PurchaseOrderItem::where('purchase_order_id', $po->id)
                ->pluck('item_id')
                ->toArray();

            foreach ($cancelItems as $item) {
                if (!in_array($item['itemId'], $poItemIds)) {
                    Log::error("[PO Supplier Cancel] Item does not belong to PO", [
                        'po_id' => $poId,
                        'item_id' => $item['itemId']
                    ]);
                    throw new Exception('Item ' . $item['itemId'] . ' does not belong to this PO');
                }
            }

            Log::info("[PO Supplier Cancel] Updating current_stock and scheduling stock for cancelled items", [
                'po_id' => $poId,
                'cancel_items' => $cancelItems
            ]);

            // Update current_stock for each cancelled item
            // Note: No validation needed as supplier is providing new stock quantity
            $this->updateCurrentStockFromCancelledItems($cancelItems);

            // Build detailed cancellation message
            $detailedMessage = $message ?? $this->buildCancellationMessage($po->po_number, $cancelItems);

            // Extract just the item IDs for storage
            $cancelledItemIds = array_column($cancelItems, 'itemId');

            // Update PO with cancellation details
            $po->update([
                'cancellation_reason' => $detailedMessage,
                'cancelled_items' => $cancelItems,
            ]);

            Log::info("[PO Supplier Cancel] Transitioning PO status to DIBATALKAN_DRAFT", [
                'po_id' => $poId,
                'po_number' => $po->po_number,
                'cancellation_message' => $detailedMessage
            ]);

            $this->statusService->transitionStatus(
                $po,
                POStatusEnum::DIBATALKAN_DRAFT,
                $detailedMessage,
                $userId
            );

            DB::commit();

            Log::info("[PO Supplier Cancel] PO cancellation completed successfully", [
                'po_id' => $poId,
                'po_number' => $po->po_number,
                'new_status' => $po->fresh()->status->value
            ]);

            return $po->fresh()->load('items');
        } catch (Exception $e) {
            DB::rollBack();

            Log::error("[PO Supplier Cancel] Failed to cancel PO", [
                'po_id' => $poId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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

    /**
     * Validate stock availability for cancelled items
     * Ensures the cancelled quantity does not exceed available stock
     */
    private function validateStockForCancelledItems(PurchaseOrder $po, array $cancelItems): void
    {
        foreach ($cancelItems as $item) {
            $stockItem = StockItem::find($item['itemId']);

            if (!$stockItem) {
                throw new Exception("Item {$item['itemName']} not found");
            }

            $availableStock = $stockItem->current_stock ?? 0;
            $requestedQty = $item['estimatedQty'] ?? 0;

            // Check if requested quantity exceeds available stock
            if ($requestedQty > $availableStock) {
                // Build error message
                $errorMessage = "Mohon maaf, untuk {$po->po_number} berikut item yang tidak dapat dipenuhi:\n\n";
                $errorMessage .= "• {$item['itemName']} (Qty: {$requestedQty} {$item['unit']})\n";
                $errorMessage .= "  Alasan: Stok tersisa {$availableStock} {$item['unit']}";

                throw new Exception($errorMessage);
            }
        }
    }

    /**
     * Update current_stock in stock_items table from cancelled items
     * This updates the current_stock field with the quantity provided by supplier
     * Also stores scheduled stock info if availableDate is provided
     * Dispatches a delayed job to process scheduled stock when time comes
     */
    private function updateCurrentStockFromCancelledItems(array $cancelledItems): void
    {
        foreach ($cancelledItems as $item) {
            $stockItem = StockItem::find($item['itemId']);

            if (!$stockItem) {
                Log::warning("[Update Current Stock] Stock item not found", [
                    'item_id' => $item['itemId']
                ]);
                continue;
            }

            $oldStock = $stockItem->current_stock;
            $newStock = $item['quantity'] ?? 0;

            // Parse availableDate if exists (format: "04/03/2026 pukul 22:58")
            $scheduledAt = null;
            if (isset($item['availableDate']) && !empty($item['availableDate'])) {
                // Parse Indonesian date format: "04/03/2026 pukul 22:58"
                $dateString = str_replace(' pukul ', ' ', $item['availableDate']);
                try {
                    $scheduledAt = \Carbon\Carbon::createFromFormat('d/m/Y H:i', trim($dateString));
                    Log::info("[Update Current Stock] Parsed scheduled date", [
                        'item_id' => $item['itemId'],
                        'item_name' => $stockItem->name,
                        'available_date' => $item['availableDate'],
                        'parsed_at' => $scheduledAt->toDateTimeString()
                    ]);
                } catch (\Exception $e) {
                    // If parsing fails, try another format or set to null
                    try {
                        $scheduledAt = \Carbon\Carbon::parse($item['availableDate']);
                        Log::info("[Update Current Stock] Parsed scheduled date (fallback)", [
                            'item_id' => $item['itemId'],
                            'item_name' => $stockItem->name,
                            'available_date' => $item['availableDate'],
                            'parsed_at' => $scheduledAt->toDateTimeString()
                        ]);
                    } catch (\Exception $e2) {
                        Log::error("[Update Current Stock] Failed to parse scheduled date", [
                            'item_id' => $item['itemId'],
                            'item_name' => $stockItem->name,
                            'available_date' => $item['availableDate'],
                            'error' => $e2->getMessage()
                        ]);
                        $scheduledAt = null;
                    }
                }
            }

            // Calculate scheduled quantity (how much will be added)
            $scheduledQuantity = 0;
            if ($scheduledAt && isset($item['estimatedQty']) && isset($item['quantity'])) {
                // scheduled_quantity = estimated_qty - current_available_qty
                $scheduledQuantity = max(0, $item['estimatedQty'] - $item['quantity']);

                Log::info("[Update Current Stock] Calculated scheduled quantity", [
                    'item_id' => $item['itemId'],
                    'item_name' => $stockItem->name,
                    'estimated_qty' => $item['estimatedQty'],
                    'current_available_qty' => $item['quantity'],
                    'scheduled_quantity' => $scheduledQuantity,
                    'scheduled_for' => $scheduledAt->toDateTimeString()
                ]);
            }

            // Update stock item with current stock and scheduled stock info
            $stockItem->update([
                'current_stock' => $newStock,
                'scheduled_quantity' => $scheduledQuantity > 0 ? $scheduledQuantity : null,
                'scheduled_at' => $scheduledAt,
                'scheduled_processed' => false,
            ]);

            Log::info("[Update Current Stock] Updated stock item", [
                'item_id' => $item['itemId'],
                'item_name' => $stockItem->name,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'scheduled_quantity' => $scheduledQuantity > 0 ? $scheduledQuantity : null,
                'scheduled_at' => $scheduledAt ? $scheduledAt->toDateTimeString() : null
            ]);

            // Dispatch delayed job if scheduled stock exists
            if ($scheduledAt && $scheduledQuantity > 0 && $scheduledAt->isFuture()) {
                $delayInSeconds = $scheduledAt->diffInSeconds(now());

                $job = \App\Jobs\ProcessScheduledStockJob::dispatch(
                    $stockItem->id,
                    $scheduledQuantity
                )->delay($scheduledAt)
                 ->uniqueId("stock_scheduled_{$stockItem->id}");

                Log::info("[Update Current Stock] Dispatched delayed job for scheduled stock", [
                    'item_id' => $item['itemId'],
                    'item_name' => $stockItem->name,
                    'job_id' => $job->uniqueId(),
                    'scheduled_quantity' => $scheduledQuantity,
                    'scheduled_at' => $scheduledAt->toDateTimeString(),
                    'delay_seconds' => $delayInSeconds,
                    'will_process_at' => $scheduledAt->toDateTimeString()
                ]);
            }
        }
    }
}
