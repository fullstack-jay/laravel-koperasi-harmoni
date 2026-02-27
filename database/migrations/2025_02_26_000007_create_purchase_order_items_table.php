<?php

declare(strict_types=1);

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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_order_id');
            $table->uuid('item_id'); // stock_item
            $table->decimal('estimated_unit_price', 10, 2)->default(0);
            $table->integer('estimated_qty')->default(0);
            $table->decimal('estimated_subtotal', 10, 2)->default(0);
            $table->decimal('actual_unit_price', 10, 2)->nullable();
            $table->integer('actual_qty')->nullable();
            $table->decimal('actual_subtotal', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->index('purchase_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
