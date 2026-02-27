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
        Schema::create('kitchen_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kitchen_order_id');
            $table->uuid('item_id');
            $table->integer('requested_qty');
            $table->integer('approved_qty')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('buy_price', 10, 2)->nullable();
            $table->decimal('profit', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('kitchen_order_id')->references('id')->on('kitchen_orders')->onDelete('cascade');
            $table->index('kitchen_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kitchen_order_items');
    }
};
