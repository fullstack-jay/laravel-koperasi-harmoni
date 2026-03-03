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
        Schema::table('stock_batches', function (Blueprint $table) {
            // Add supplier_id for better tracing
            $table->uuid('supplier_id')->nullable()->after('po_id');

            // Add supplier invoice number for reference
            $table->string('supplier_invoice_no')->nullable()->after('supplier_id');

            // Add condition field to track item quality
            $table->enum('condition', ['good', 'damaged', 'near_expired'])->default('good')->after('status');

            // Add notes for batch-specific information
            $table->text('batch_notes')->nullable()->after('condition');

            // Indexes for better query performance
            $table->index(['supplier_id', 'received_date']);
            $table->index(['item_id', 'expiry_date', 'status']); // Composite index for FEFO
        });

        // Add foreign key constraint
        Schema::table('stock_batches', function (Blueprint $table) {
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropIndex(['supplier_id', 'received_date']);
            $table->dropIndex(['item_id', 'expiry_date', 'status']);
            $table->dropColumn(['supplier_id', 'supplier_invoice_no', 'condition', 'batch_notes']);
        });
    }
};
