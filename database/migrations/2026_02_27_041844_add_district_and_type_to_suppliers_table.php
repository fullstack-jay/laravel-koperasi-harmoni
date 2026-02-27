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
        Schema::table('suppliers', function (Blueprint $table) {
            // Add district column (daerah/kota/kabupaten)
            if (!Schema::hasColumn('suppliers', 'district')) {
                $table->string('district')->nullable()->after('address');
            }

            // Add supplier_type column (PKG, RAW, dll)
            if (!Schema::hasColumn('suppliers', 'supplier_type')) {
                $table->string('supplier_type')->nullable()->after('district');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'district')) {
                $table->dropColumn('district');
            }
            if (Schema::hasColumn('suppliers', 'supplier_type')) {
                $table->dropColumn('supplier_type');
            }
        });
    }
};
