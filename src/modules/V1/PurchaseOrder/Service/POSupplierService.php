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

            if ($po->status !== POStatusEnum::MENUNGGU_PERSETUJUAN_SUPPLIER) {
                Log::error("[PO Supplier Confirm] Invalid PO status", [
                    'po_id' => $poId,
                    'po_number' => $po->po_number,
                    'current_status' => $po->status->value,
                    'expected_status' => POStatusEnum::MENUNGGU_PERSETUJUAN_SUPPLIER->value
                ]);
                throw new Exception('PO must be in MENUNGGU_PERSETUJUAN_SUPPLIER status');
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

            // Always save expired tracking information regardless of price changes
            $this->updateExpiredTracking($po->id, $items);

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
     * and update buy_price, last_price_update_at, and expired tracking in stock_items master table
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

            // Prepare expired tracking data
            $expiredData = $this->prepareExpiredTrackingData($itemData);

            Log::info("[PO Supplier Confirm] Updating stock item with expired tracking", [
                'po_id' => $poId,
                'item_id' => $poItem->item_id,
                'item_name' => $stockItem->name,
                'is_same_expired' => $expiredData['is_same_expired'],
            ]);

            // Update stock_items master table with new price, timestamp, and expired tracking
            $stockItem->update(array_merge([
                'buy_price' => $newPrice,
                'last_price_update_at' => now(),
            ], $expiredData));
        }
    }

    /**
     * Prepare expired tracking data array from request item data
     * Supports both old format (isSameExpired) and new format (isSameExpiry with expiredBatches)
     */
    private function prepareExpiredTrackingData(array $itemData): array
    {
        // Support both old and new field names for backward compatibility
        $isSameExpiry = $itemData['isSameExpiry'] ?? $itemData['isSameExpired'] ?? false;

        $expiredData = [
            'is_same_expired' => $isSameExpiry,
        ];

        if ($isSameExpiry) {
            // If same expiry date
            $expiryDate = $itemData['expiryDate'] ?? $itemData['tanggalExpired'] ?? null;

            $expiredData['tanggal_expired'] = $expiryDate;
            $expiredData['quantity_expired_terdekat'] = null;
            $expiredData['tanggal_expired_terdekat'] = null;
            $expiredData['quantity_expired_terjauh'] = null;
            $expiredData['tanggal_expired_terjauh'] = null;

            Log::info("[Prepare Expired Data] Same expiry date", [
                'expiry_date' => $expiryDate,
            ]);
        } else {
            // If different expiry dates - extract from expiredBatches array
            $batches = $itemData['expiredBatches'] ?? [];

            if (!empty($batches)) {
                // Sort batches by expiry date to find nearest and furthest
                usort($batches, function($a, $b) {
                    return strtotime($a['expiryDate']) - strtotime($b['expiryDate']);
                });

                $nearestBatch = $batches[0];
                $furthestBatch = $batches[count($batches) - 1];

                $expiredData['tanggal_expired'] = null;
                $expiredData['quantity_expired_terdekat'] = $nearestBatch['quantity'];
                $expiredData['tanggal_expired_terdekat'] = $nearestBatch['expiryDate'];
                $expiredData['quantity_expired_terjauh'] = $furthestBatch['quantity'];
                $expiredData['tanggal_expired_terjauh'] = $furthestBatch['expiryDate'];

                Log::info("[Prepare Expired Data] Different expiry dates from batches", [
                    'nearest_expiry' => $nearestBatch['expiryDate'],
                    'nearest_qty' => $nearestBatch['quantity'],
                    'furthest_expiry' => $furthestBatch['expiryDate'],
                    'furthest_qty' => $furthestBatch['quantity'],
                    'total_batches' => count($batches),
                ]);
            } else {
                // Fallback to old format if no batches provided
                $expiredData['tanggal_expired'] = null;
                $expiredData['quantity_expired_terdekat'] = $itemData['quantityExpiredTerdekat'] ?? 0;
                $expiredData['tanggal_expired_terdekat'] = $itemData['tanggalExpiredTerdekat'] ?? null;
                $expiredData['quantity_expired_terjauh'] = $itemData['quantityExpiredTerjauh'] ?? 0;
                $expiredData['tanggal_expired_terjauh'] = $itemData['tanggalExpiredTerjauh'] ?? null;

                Log::info("[Prepare Expired Data] Different expiry dates (old format)", [
                    'nearest_expiry' => $itemData['tanggalExpiredTerdekat'] ?? null,
                    'nearest_qty' => $itemData['quantityExpiredTerdekat'] ?? 0,
                    'furthest_expiry' => $itemData['tanggalExpiredTerjauh'] ?? null,
                    'furthest_qty' => $itemData['quantityExpiredTerjauh'] ?? 0,
                ]);
            }
        }

        return $expiredData;
    }

    /**
     * Update expired tracking information in stock_items master table
     * Called during PO confirmation regardless of price changes
     */
    private function updateExpiredTracking(string $poId, array $itemsData): void
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

            // Find stock item
            $stockItem = \Modules\V1\Stock\Models\StockItem::find($poItem->item_id);

            if (!$stockItem) {
                continue;
            }

            // Prepare expired tracking data
            $expiredData = $this->prepareExpiredTrackingData($itemData);

            Log::info("[PO Supplier Confirm] Updating stock item with expired tracking", [
                'po_id' => $poId,
                'po_item_id' => $poItem->id,
                'item_id' => $poItem->item_id,
                'item_name' => $stockItem->name,
                'is_same_expired' => $expiredData['is_same_expired'],
            ]);

            // Update stock_items master table with expired tracking only
            $stockItem->update($expiredData);

            // Save expiry batches if provided
            $this->saveExpiryBatches($po, $poItem, $stockItem, $itemData);
        }
    }

    /**
     * Save expiry batches to stock_expiry_batches table
     * Called when supplier provides batch expiry information
     */
    private function saveExpiryBatches(
        PurchaseOrder $po,
        PurchaseOrderItem $poItem,
        \Modules\V1\Stock\Models\StockItem $stockItem,
        array $itemData
    ): void {
        // Support both old and new field names
        $isSameExpiry = $itemData['isSameExpiry'] ?? $itemData['isSameExpired'] ?? false;
        $batches = $itemData['expiredBatches'] ?? [];

        // Delete existing unprocessed batches for this PO item
        \Modules\V1\Stock\Models\StockExpiryBatch::where('purchase_order_item_id', $poItem->id)
            ->where('is_processed', false)
            ->delete();

        // Only save batches if isSameExpiry is false and batches are provided
        if (!$isSameExpiry && !empty($batches)) {
            foreach ($batches as $batchData) {
                \Modules\V1\Stock\Models\StockExpiryBatch::create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'purchase_order_item_id' => $poItem->id,
                    'purchase_order_id' => $po->id,
                    'supplier_id' => $po->supplier_id,
                    'stock_item_id' => $stockItem->id,
                    'item_name' => $stockItem->name, // Snapshot of item name at PO time
                    'batch_number' => $batchData['batchNumber'],
                    'quantity' => $batchData['quantity'],
                    'expiry_date' => $batchData['expiryDate'],
                    'is_processed' => false,
                    'processed_at' => null,
                ]);
            }

            Log::info("[PO Supplier Confirm] Saved expiry batches", [
                'po_id' => $po->id,
                'po_item_id' => $poItem->id,
                'supplier_id' => $po->supplier_id,
                'stock_item_id' => $stockItem->id,
                'item_name' => $stockItem->name,
                'total_batches' => count($batches),
                'batches' => $batches,
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

            if ($po->status !== POStatusEnum::MENUNGGU_PERSETUJUAN_SUPPLIER) {
                Log::error("[PO Supplier Reject] Invalid PO status", [
                    'po_id' => $poId,
                    'po_number' => $po->po_number,
                    'current_status' => $po->status->value,
                    'expected_status' => POStatusEnum::MENUNGGU_PERSETUJUAN_SUPPLIER->value
                ]);
                throw new Exception('PO must be in MENUNGGU_PERSETUJUAN_SUPPLIER status');
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

            if ($po->status !== POStatusEnum::MENUNGGU_PERSETUJUAN_SUPPLIER) {
                Log::error("[PO Supplier Cancel] Invalid PO status", [
                    'po_id' => $poId,
                    'po_number' => $po->po_number,
                    'current_status' => $po->status->value,
                    'expected_status' => POStatusEnum::MENUNGGU_PERSETUJUAN_SUPPLIER->value
                ]);
                throw new Exception('PO must be in MENUNGGU_PERSETUJUAN_SUPPLIER status to be cancelled');
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
     * Stores expired tracking information for stock items
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

            // Parse availableDate if provided (format: "2026-03-04" or ISO date format)
            $scheduledAt = null;
            if (isset($item['availableDate']) && !empty($item['availableDate'])) {
                try {
                    $scheduledAt = \Carbon\Carbon::parse($item['availableDate']);
                    Log::info("[Update Current Stock] Parsed scheduled date", [
                        'item_id' => $item['itemId'],
                        'item_name' => $stockItem->name,
                        'available_date' => $item['availableDate'],
                        'parsed_at' => $scheduledAt->toDateTimeString()
                    ]);
                } catch (\Exception $e) {
                    Log::error("[Update Current Stock] Failed to parse scheduled date", [
                        'item_id' => $item['itemId'],
                        'item_name' => $stockItem->name,
                        'available_date' => $item['availableDate'],
                        'error' => $e->getMessage()
                    ]);
                    $scheduledAt = null;
                }
            }

            // Get scheduled quantity from stokBertambah field
            // This is the quantity that will be added to current_stock in the future
            $scheduledQuantity = 0;
            if (isset($item['stokBertambah']) && $item['stokBertambah'] > 0) {
                $scheduledQuantity = (int) $item['stokBertambah'];

                Log::info("[Update Current Stock] Scheduled quantity provided", [
                    'item_id' => $item['itemId'],
                    'item_name' => $stockItem->name,
                    'stok_bertambah' => $scheduledQuantity,
                    'scheduled_for' => $scheduledAt ? $scheduledAt->toDateTimeString() : 'not set',
                    'note' => $scheduledAt
                        ? 'This stock will be added to current_stock on scheduled date'
                        : 'WARNING: stokBertambah provided but no availableDate set'
                ]);
            }

            // Prepare expired tracking data
            $expiredData = [
                'is_same_expired' => $item['isSameExpired'] ?? false,
            ];

            if ($item['isSameExpired']) {
                // If same expiry date
                $expiredData['tanggal_expired'] = $item['tanggalExpired'] ?? null;
                $expiredData['quantity_expired_terdekat'] = null;
                $expiredData['tanggal_expired_terdekat'] = null;
                $expiredData['quantity_expired_terjauh'] = null;
                $expiredData['tanggal_expired_terjauh'] = null;

                Log::info("[Update Current Stock] Processing same expired date", [
                    'item_id' => $item['itemId'],
                    'item_name' => $stockItem->name,
                    'tanggal_expired' => $item['tanggalExpired'] ?? null,
                ]);
            } else {
                // If different expiry dates
                $expiredData['tanggal_expired'] = null;
                $expiredData['quantity_expired_terdekat'] = $item['quantityExpiredTerdekat'] ?? 0;
                $expiredData['tanggal_expired_terdekat'] = $item['tanggalExpiredTerdekat'] ?? null;
                $expiredData['quantity_expired_terjauh'] = $item['quantityExpiredTerjauh'] ?? 0;
                $expiredData['tanggal_expired_terjauh'] = $item['tanggalExpiredTerjauh'] ?? null;

                Log::info("[Update Current Stock] Processing different expired dates", [
                    'item_id' => $item['itemId'],
                    'item_name' => $stockItem->name,
                    'nearest_expiry' => $item['tanggalExpiredTerdekat'] ?? null,
                    'nearest_qty' => $item['quantityExpiredTerdekat'] ?? 0,
                    'furthest_expiry' => $item['tanggalExpiredTerjauh'] ?? null,
                    'furthest_qty' => $item['quantityExpiredTerjauh'] ?? 0,
                ]);
            }

            // Update stock item with current stock, scheduled stock info, and expired tracking
            $stockItem->update(array_merge([
                'current_stock' => $newStock,
                'scheduled_quantity' => $scheduledQuantity > 0 ? $scheduledQuantity : null,
                'scheduled_at' => $scheduledAt,
                'scheduled_processed' => false,
            ], $expiredData));

            Log::info("[Update Current Stock] Updated stock item with expired tracking", [
                'item_id' => $item['itemId'],
                'item_name' => $stockItem->name,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'scheduled_quantity' => $scheduledQuantity > 0 ? $scheduledQuantity : null,
                'scheduled_at' => $scheduledAt ? $scheduledAt->toDateTimeString() : null,
                'is_same_expired' => $expiredData['is_same_expired'],
                'tanggal_expired' => $expiredData['tanggal_expired'] ?? null,
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
