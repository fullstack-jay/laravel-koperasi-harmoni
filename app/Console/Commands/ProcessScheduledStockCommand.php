<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\V1\Stock\Models\StockItem;
use Illuminate\Support\Facades\DB;

class ProcessScheduledStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:process-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled stock additions and update current_stock when scheduled time is reached';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing scheduled stock...');

        // Find all stock items with scheduled stock that should be processed
        $stockItems = StockItem::whereNotNull('scheduled_quantity')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_processed', false)
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($stockItems->isEmpty()) {
            $this->info('No scheduled stock to process.');
            return Command::SUCCESS;
        }

        $this->info("Found {$stockItems->count()} items to process.");

        DB::beginTransaction();

        try {
            foreach ($stockItems as $stockItem) {
                $this->info("Processing {$stockItem->name} ({$stockItem->code})");
                $this->info("  Current stock: {$stockItem->current_stock}");
                $this->info("  Adding: {$stockItem->scheduled_quantity}");

                // Add scheduled quantity to current stock
                $newStock = $stockItem->current_stock + $stockItem->scheduled_quantity;

                // Update stock item
                $stockItem->update([
                    'current_stock' => $newStock,
                    'scheduled_quantity' => null,
                    'scheduled_at' => null,
                    'scheduled_processed' => true,
                ]);

                $this->info("  New stock: {$newStock}");
                $this->info("  ✓ Processed successfully");
            }

            DB::commit();
            $this->info('✓ All scheduled stock processed successfully.');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("✗ Error processing scheduled stock: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
