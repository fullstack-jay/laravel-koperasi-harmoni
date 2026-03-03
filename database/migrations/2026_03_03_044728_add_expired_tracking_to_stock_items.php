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
            // Expired tracking fields
            $table->boolean('is_same_expired')->default(false)->after('current_stock');
            $table->date('tanggal_expired')->nullable()->after('is_same_expired');
            $table->integer('quantity_expired_terdekat')->default(0)->after('tanggal_expired');
            $table->date('tanggal_expired_terdekat')->nullable()->after('quantity_expired_terdekat');
            $table->integer('quantity_expired_terjauh')->default(0)->after('tanggal_expired_terdekat');
            $table->date('tanggal_expired_terjauh')->nullable()->after('quantity_expired_terjauh');

            // Indexes for better query performance
            $table->index('is_same_expired');
            $table->index('tanggal_expired');
            $table->index('tanggal_expired_terdekat');
            $table->index('tanggal_expired_terjauh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropIndex(['is_same_expired']);
            $table->dropIndex(['tanggal_expired']);
            $table->dropIndex(['tanggal_expired_terdekat']);
            $table->dropIndex(['tanggal_expired_terjauh']);
            $table->dropColumn([
                'is_same_expired',
                'tanggal_expired',
                'quantity_expired_terdekat',
                'tanggal_expired_terdekat',
                'quantity_expired_terjauh',
                'tanggal_expired_terjauh'
            ]);
        });
    }
};
