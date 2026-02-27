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
        Schema::table('users', function (Blueprint $table) {
            // Add supplier_id for PEMASOK role
            if (!Schema::hasColumn('users', 'supplier_id')) {
                $table->uuid('supplier_id')->nullable()->after('role_id');
                $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
                $table->index('supplier_id');
            }

            // Add dapur_id for DAPUR role
            if (!Schema::hasColumn('users', 'dapur_id')) {
                $table->uuid('dapur_id')->nullable()->after('supplier_id');
                $table->foreign('dapur_id')->references('id')->on('dapurs')->onDelete('set null');
                $table->index('dapur_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'supplier_id')) {
                $table->dropForeign(['supplier_id']);
                $table->dropIndex(['supplier_id']);
                $table->dropColumn('supplier_id');
            }

            if (Schema::hasColumn('users', 'dapur_id')) {
                $table->dropForeign(['dapur_id']);
                $table->dropIndex(['dapur_id']);
                $table->dropColumn('dapur_id');
            }
        });
    }
};
