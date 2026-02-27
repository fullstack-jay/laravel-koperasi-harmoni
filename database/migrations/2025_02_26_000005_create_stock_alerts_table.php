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
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('item_id');
            $table->uuid('batch_id')->nullable();
            $table->enum('alert_type', ['low_stock', 'out_of_stock', 'expired', 'expiring_soon']);
            $table->enum('severity', ['critical', 'warning', 'info']);
            $table->text('message');
            $table->integer('current_qty');
            $table->integer('threshold')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('days_to_expiry')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('stock_items')->onDelete('cascade');
            $table->foreign('batch_id')->references('id')->on('stock_batches')->onDelete('cascade');
            $table->index('alert_type');
            $table->index('is_resolved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
    }
};
