<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TruncatePurchaseOrdersTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'po:truncate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate purchase_orders and purchase_order_items tables (WARNING: This will delete all data)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Confirm with user
        if (! $this->confirm('⚠️  WARNING: This will delete ALL purchase orders and items. Are you sure?')) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $this->info('Truncating purchase orders tables...');

        try {
            // Delete from child table first (purchase_order_items)
            $itemsCount = DB::table('purchase_order_items')->count();
            DB::table('purchase_order_items')->delete();
            $this->info("✓ Deleted {$itemsCount} purchase order items");

            // Delete from parent table (purchase_orders)
            $ordersCount = DB::table('purchase_orders')->count();
            DB::table('purchase_orders')->delete();
            $this->info("✓ Deleted {$ordersCount} purchase orders");

            $this->newLine();
            $this->info('✅ Purchase orders tables truncated successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error truncating tables: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
