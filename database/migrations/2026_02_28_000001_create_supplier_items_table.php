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
        Schema::create('supplier_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('supplier_id');
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit')->nullable(); // kg, liter, pcs, etc
            $table->integer('min_stock')->default(0);
            $table->integer('max_stock')->default(0);
            $table->decimal('buy_price', 10, 2)->default(0); // auto-sync from sell_price
            $table->decimal('sell_price', 10, 2)->default(0); // updated by supplier
            $table->decimal('avg_weight', 5, 2)->nullable(); // average weight in kg
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Composite index for supplier items filtering
            $table->index(['supplier_id', 'status']);
            $table->index('code');
            $table->index('updated_at'); // for sorting by last update date
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_items');
    }
};
