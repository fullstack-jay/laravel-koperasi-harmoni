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
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('item_id');
            $table->string('batch_number')->unique();
            $table->integer('quantity'); // original qty
            $table->integer('remaining_qty'); // available qty
            $table->decimal('buy_price', 10, 2);
            $table->date('expiry_date');
            $table->string('location')->nullable(); // Gudang A-Rak 1
            $table->enum('status', ['available', 'allocated', 'expired'])->default('available');
            $table->date('received_date');
            $table->uuid('po_id')->nullable(); // will be foreign key later
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('stock_items')->onDelete('cascade');
            $table->index(['item_id', 'expiry_date']); // CRITICAL for FEFO
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
