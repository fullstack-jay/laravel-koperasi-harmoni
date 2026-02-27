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
        Schema::table('system_users', function (Blueprint $table) {
            if (!Schema::hasColumn('system_users', 'supplier_id')) {
                $table->uuid('supplier_id')->nullable()->after('username');
                $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
                $table->index('supplier_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_users', function (Blueprint $table) {
            if (Schema::hasColumn('system_users', 'supplier_id')) {
                $table->dropForeign(['supplier_id']);
                $table->dropIndex(['supplier_id']);
                $table->dropColumn('supplier_id');
            }
        });
    }
};
