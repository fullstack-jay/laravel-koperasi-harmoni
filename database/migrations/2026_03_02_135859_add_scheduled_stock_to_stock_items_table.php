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
        Schema::table('stock_items', function (Blueprint $table) {
            $table->integer('scheduled_quantity')->nullable()->after('current_stock');
            $table->timestamp('scheduled_at')->nullable()->after('scheduled_quantity');
            $table->boolean('scheduled_processed')->default(false)->after('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropColumn(['scheduled_quantity', 'scheduled_at', 'scheduled_processed']);
        });
    }
};
