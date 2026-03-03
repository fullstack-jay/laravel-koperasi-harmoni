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
        Schema::create('stock_expiry_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('stock_item_id');
            $table->integer('batch_number');
            $table->integer('quantity');
            $table->date('expiry_date');
            $table->boolean('is_processed')->default(false)->comment('Has this batch been added to current_stock');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('stock_item_id')->references('id')->on('stock_items')->onDelete('cascade');

            // Indexes
            $table->index(['stock_item_id', 'expiry_date']);
            $table->index('expiry_date');
            $table->index('is_processed');
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
