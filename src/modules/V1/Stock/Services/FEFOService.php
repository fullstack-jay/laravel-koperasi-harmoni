<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Services;

use Modules\V1\Stock\Models\StockBatch;
use Illuminate\Support\Facades\DB;

class FEFOService
{
    /**
     * Allocate stock using FEFO (First Expired First Out)
     *
     * @param  string  $itemId  Stock item ID
     * @param  int  $requiredQty  Required quantity
     * @return array|null Selected batches or null if insufficient
     */
    public function allocateStock(string $itemId, int $requiredQty): ?array
    {
        // Get available, non-expired batches sorted by expiry date
        $batches = StockBatch::where('item_id', $itemId)
            ->where('status', 'available')
            ->where('expiry_date', '>', now())
            ->orderBy('expiry_date', 'asc')  // FEFO: Earliest first
            ->lockForUpdate()  // Prevent race conditions
            ->get();

        if ($batches->isEmpty()) {
            return null;
        }

        $selectedBatches = [];
        $allocatedQty = 0;

        foreach ($batches as $batch) {
            if ($allocatedQty >= $requiredQty) {
                break;
            }

            $remainingNeeded = $requiredQty - $allocatedQty;
            $qtyFromBatch = min($batch->remaining_qty, $remainingNeeded);

            $selectedBatches[] = [
                'batchId' => $batch->id,
                'batchNumber' => $batch->batch_number,
                'qty' => $qtyFromBatch,
                'buyPrice' => (float) $batch->buy_price,
                'expiryDate' => $batch->expiry_date->format('Y-m-d'),
            ];

            $allocatedQty += $qtyFromBatch;
        }

        // Check if we have enough stock
        if ($allocatedQty < $requiredQty) {
            return null;  // Insufficient stock
        }

        return $selectedBatches;
    }

    /**
     * Get total available quantity for an item
     */
    public function getAvailableStock(string $itemId): int
    {
        return StockBatch::where('item_id', $itemId)
            ->where('status', 'available')
            ->where('expiry_date', '>', now())
            ->sum('remaining_qty');
    }

    /**
     * Check if stock is available for items
     *
     * @param  array  $items  Array of items [['itemId' => 'xxx', 'qty' => 10], ...]
     * @return array Available status for each item
     */
    public function checkStockAvailability(array $items): array
    {
        $results = [];

        foreach ($items as $item) {
            $itemId = $item['itemId'];
            $requiredQty = $item['qty'];
            $available = $this->getAvailableStock($itemId);

            $results[$itemId] = [
                'available' => $available >= $requiredQty,
                'required' => $requiredQty,
                'available_qty' => $available,
                'shortage' => $available < $requiredQty ? ($requiredQty - $available) : 0,
            ];
        }

        return $results;
    }

    /**
     * Confirm stock allocation (update batch quantities)
     *
     * @param  array  $selectedBatches  Array of selected batches from allocateStock
     * @return bool Success status
     */
    public function confirmAllocation(array $selectedBatches): bool
    {
        return DB::transaction(function () use ($selectedBatches) {
            foreach ($selectedBatches as $selected) {
                $batch = StockBatch::find($selected['batchId']);

                if (! $batch) {
                    throw new \Exception("Batch {$selected['batchId']} not found");
                }

                $batch->remaining_qty -= $selected['qty'];

                // Mark as allocated if no remaining quantity
                if ($batch->remaining_qty <= 0) {
                    $batch->status = 'allocated';
                    $batch->remaining_qty = 0;
                }

                $batch->save();
            }

            return true;
        });
    }

    /**
     * Get batches expiring soon
     *
     * @param  string  $itemId  Stock item ID
     * @param  int  $days  Days threshold
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiringBatches(string $itemId, int $days = 7)
    {
        return StockBatch::where('item_id', $itemId)
            ->where('status', 'available')
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays($days))
            ->orderBy('expiry_date', 'asc')
            ->get();
    }

    /**
     * Get expired batches
     *
     * @param  string  $itemId  Stock item ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiredBatches(string $itemId)
    {
        return StockBatch::where('item_id', $itemId)
            ->where('expiry_date', '<=', now())
            ->orderBy('expiry_date', 'desc')
            ->get();
    }
}
