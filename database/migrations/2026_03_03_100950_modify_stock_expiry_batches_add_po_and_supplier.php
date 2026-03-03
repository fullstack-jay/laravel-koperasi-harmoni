<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop old table
        Schema::dropIfExists('stock_expiry_batches');

        // Create new table with correct structure
        Schema::create('stock_expiry_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_order_item_id')->comment('Reference to PO item');
            $table->uuid('purchase_order_id')->comment('Reference to PO');
            $table->uuid('supplier_id')->comment('Reference to supplier');
            $table->uuid('stock_item_id')->nullable()->comment('Reference to master stock item (optional)');
            $table->string('item_name')->comment('Item name at time of PO (snapshot)');
            $table->integer('batch_number');
            $table->integer('quantity');
            $table->date('expiry_date');
            $table->boolean('is_processed')->default(false)->comment('Has this batch been added to current_stock');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('purchase_order_item_id')->references('id')->on('purchase_order_items')->onDelete('cascade');
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('stock_item_id')->references('id')->on('stock_items')->onDelete('set null');

            // Indexes
            $table->index(['purchase_order_item_id', 'expiry_date']);
            $table->index('purchase_order_id');
            $table->index('supplier_id');
            $table->index('expiry_date');
            $table->index('is_processed');
            $table->index(['purchase_order_id', 'stock_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_expiry_batches');
    }
};
