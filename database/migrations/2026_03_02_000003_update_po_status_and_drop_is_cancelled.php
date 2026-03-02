<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Update existing cancelled draft records (is_cancelled = true) to DIBATALKAN_DRAFT status
        DB::statement("UPDATE purchase_orders SET status = 'dibatalkan_draft' WHERE is_cancelled = true AND status = 'draft'");

        // Step 2: Drop the enum constraint
        DB::statement("ALTER TABLE purchase_orders DROP CONSTRAINT purchase_orders_status_check");

        // Step 3: Recreate the enum constraint with 'dibatalkan_draft' included
        DB::statement("ALTER TABLE purchase_orders ADD CONSTRAINT purchase_orders_status_check CHECK (status::varchar(50) = ANY (ARRAY['draft'::varchar(50), 'dibatalkan_draft'::varchar(50), 'terkirim'::varchar(50), 'perubahan_harga'::varchar(50), 'dikonfirmasi_supplier'::varchar(50), 'dikonfirmasi_koperasi'::varchar(50), 'selesai'::varchar(50), 'dibatalkan'::varchar(50)]))");

        // Step 4: Drop the is_cancelled column
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('is_cancelled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Restore is_cancelled column
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->boolean('is_cancelled')->default(false)->after('status');
        });

        // Step 2: Revert DIBATALKAN_DRAFT records back to DRAFT with is_cancelled = true
        DB::statement("UPDATE purchase_orders SET status = 'draft', is_cancelled = true WHERE status = 'dibatalkan_draft'");

        // Step 3: Drop the enum constraint
        DB::statement("ALTER TABLE purchase_orders DROP CONSTRAINT purchase_orders_status_check");

        // Step 4: Recreate the original enum constraint without 'dibatalkan_draft'
        DB::statement("ALTER TABLE purchase_orders ADD CONSTRAINT purchase_orders_status_check CHECK (status::varchar(50) = ANY (ARRAY['draft'::varchar(50), 'terkirim'::varchar(50), 'perubahan_harga'::varchar(50), 'dikonfirmasi_supplier'::varchar(50), 'dikonfirmasi_koperasi'::varchar(50), 'selesai'::varchar(50), 'dibatalkan'::varchar(50)]))");
    }
};
