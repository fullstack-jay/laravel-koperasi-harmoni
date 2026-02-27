<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\V1\Stock\Models\StockBatch;
use Modules\V1\Stock\Models\StockItem;
use Shared\Helpers\DocumentHelper;

class BatchManagementService
{
    /**
     * Create a new stock batch
     */
    public function createBatch(array $data): StockBatch
    {
        return DB::transaction(function () use ($data) {
            // Validate item exists
            $item = StockItem::find($data['item_id']);
            if (! $item) {
                throw new Exception('Stock item not found', 404);
            }

            // Generate batch number
            $date = now()->format('Y-m-d');
            $lastBatch = StockBatch::whereDate('created_at', today())
                ->orderBy('batch_number', 'desc')
                ->first();

            $sequence = 1;
            if ($lastBatch) {
                // Extract sequence from last batch number
                $parts = explode('-', $lastBatch->batch_number);
                $sequence = (int) end($parts) + 1;
            }

            $batchNumber = DocumentHelper::generateBatchNumber(
                $item->code,
                $date,
                $sequence
            );

            $batch = StockBatch::create([
                'item_id' => $data['item_id'],
                'batch_number' => $batchNumber,
                'quantity' => $data['quantity'],
                'remaining_qty' => $data['quantity'],
                'buy_price' => $data['buy_price'],
                'expiry_date' => $data['expiry_date'],
                'location' => $data['location'] ?? null,
                'status' => 'available',
                'received_date' => $data['received_date'] ?? now()->format('Y-m-d'),
                'po_id' => $data['po_id'] ?? null,
            ]);

            // Update item's current stock
            $item->increment('current_stock', $data['quantity']);

            return $batch;
        });
    }

    /**
     * Update batch status
     */
    public function updateBatchStatus(string $batchId, string $status): StockBatch
    {
        $batch = StockBatch::find($batchId);

        if (! $batch) {
            throw new Exception('Batch not found', 404);
        }

        $batch->update(['status' => $status]);

        return $batch->fresh();
    }

    /**
     * Get batch by ID
     */
    public function getBatch(string $batchId): StockBatch
    {
        $batch = StockBatch::find($batchId);

        if (! $batch) {
            throw new Exception('Batch not found', 404);
        }

        return $batch;
    }

    /**
     * Get batches by item ID
     */
    public function getBatchesByItem(string $itemId, ?string $status = null)
    {
        $query = StockBatch::where('item_id', $itemId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('expiry_date', 'asc')->get();
    }

    /**
     * Mark expired batches
     */
    public function markExpiredBatches(): int
    {
        $expiredCount = StockBatch::where('expiry_date', '<=', now())
            ->where('status', 'available')
            ->update(['status' => 'expired']);

        return $expiredCount;
    }
}
