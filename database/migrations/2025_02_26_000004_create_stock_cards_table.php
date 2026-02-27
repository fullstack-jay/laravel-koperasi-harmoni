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
        Schema::create('stock_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('item_id');
            $table->uuid('batch_id')->nullable();
            $table->date('date');
            $table->enum('type', ['in', 'out', 'adjustment', 'opname']);
            $table->string('reference')->nullable(); // PO number, Order number, etc
            $table->uuid('reference_id')->nullable();
            $table->integer('qty_in')->default(0);
            $table->integer('qty_out')->default(0);
            $table->integer('balance'); // running balance
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('stock_items')->onDelete('cascade');
            $table->foreign('batch_id')->references('id')->on('stock_batches')->onDelete('set null');
            $table->index(['item_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_cards');
    }
};
