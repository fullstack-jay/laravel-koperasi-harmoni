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
        Schema::table('purchase_order_items', function (Blueprint $table) {
            // Add confirmed columns after estimated columns
            $table->integer('confirmed_qty')->nullable()->after('estimated_qty');
            $table->decimal('confirmed_unit_price', 10, 2)->nullable()->after('estimated_unit_price');
            $table->decimal('confirmed_subtotal', 10, 2)->nullable()->after('estimated_subtotal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['confirmed_qty', 'confirmed_unit_price', 'confirmed_subtotal']);
        });
    }
};
