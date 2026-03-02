<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\V1\Stock\Models\StockItem;

class ProcessScheduledStockJob implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of exceptions allowed before the job fails.
     */
    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $stockItemId,
        public int $quantityToAdd
    ) {
        // Set unique job ID to prevent duplicate processing
        $this->uniqueId = "stock_scheduled_{$stockItemId}";
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $stockItem = StockItem::find($this->stockItemId);

        if (!$stockItem) {
            Log::warning("Stock item {$this->stockItemId} not found for scheduled stock processing");
            return;
        }

        // Double check if scheduled stock hasn't been processed yet
        if ($stockItem->scheduled_processed) {
            Log::info("Stock item {$stockItem->name} ({$stockItem->code}) - scheduled stock already processed, skipping");
            return;
        }

        DB::beginTransaction();

        try {
            $oldStock = $stockItem->current_stock;
            $newStock = $oldStock + $this->quantityToAdd;

            // Update stock item
            $stockItem->update([
                'current_stock' => $newStock,
                'scheduled_quantity' => null,
                'scheduled_at' => null,
                'scheduled_processed' => true,
            ]);

            DB::commit();

            Log::info("Stock item {$stockItem->name} ({$stockItem->code}) - scheduled stock added: {$oldStock} + {$this->quantityToAdd} = {$newStock}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to process scheduled stock for item {$this->stockItemId}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "stock_scheduled_{$this->stockItemId}";
    }
}
