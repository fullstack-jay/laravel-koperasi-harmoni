<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop old check constraint
        DB::statement("ALTER TABLE purchase_orders DROP CONSTRAINT IF EXISTS purchase_orders_status_check");

        // Update all POs with status 'dibatalkan' to 'dibatalkan_koperasi' before adding new constraint
        DB::table('purchase_orders')
            ->where('status', 'dibatalkan')
            ->update(['status' => 'dibatalkan_koperasi']);

        // Add new check constraint without 'dibatalkan', with 'dibatalkan_koperasi'
        DB::statement("ALTER TABLE purchase_orders ADD CONSTRAINT purchase_orders_status_check CHECK (status::varchar(50) = ANY (ARRAY['draft'::varchar(50), 'dibatalkan_draft'::varchar(50), 'terkirim'::varchar(50), 'perubahan_harga'::varchar(50), 'dikonfirmasi_supplier'::varchar(50), 'dikonfirmasi_koperasi'::varchar(50), 'selesai'::varchar(50), 'dibatalkan_koperasi'::varchar(50)]))");

        // Update status history records as well
        DB::table('po_status_histories')
            ->where('to_status', 'dibatalkan')
            ->update(['to_status' => 'dibatalkan_koperasi']);

        DB::table('po_status_histories')
            ->where('from_status', 'dibatalkan')
            ->update(['from_status' => 'dibatalkan_koperasi']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new check constraint
        DB::statement("ALTER TABLE purchase_orders DROP CONSTRAINT IF EXISTS purchase_orders_status_check");

        // Revert data back to 'dibatalkan'
        DB::table('purchase_orders')
            ->where('status', 'dibatalkan_koperasi')
            ->update(['status' => 'dibatalkan']);

        // Add old check constraint with 'dibatalkan', without 'dibatalkan_koperasi'
        DB::statement("ALTER TABLE purchase_orders ADD CONSTRAINT purchase_orders_status_check CHECK (status::varchar(50) = ANY (ARRAY['draft'::varchar(50), 'dibatalkan_draft'::varchar(50), 'terkirim'::varchar(50), 'perubahan_harga'::varchar(50), 'dikonfirmasi_supplier'::varchar(50), 'dikonfirmasi_koperasi'::varchar(50), 'selesai'::varchar(50), 'dibatalkan'::varchar(50)]))");

        // Revert status history records
        DB::table('po_status_histories')
            ->where('to_status', 'dibatalkan_koperasi')
            ->update(['to_status' => 'dibatalkan']);

        DB::table('po_status_histories')
            ->where('from_status', 'dibatalkan_koperasi')
            ->update(['from_status' => 'dibatalkan']);
    }
};
