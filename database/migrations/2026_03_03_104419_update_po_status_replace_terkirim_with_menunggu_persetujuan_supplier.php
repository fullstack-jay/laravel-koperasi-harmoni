<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Replace 'terkirim' with 'menunggu_persetujuan_supplier' in PO status enum
     */
    public function up(): void
    {
        // First, drop the existing enum constraint to allow status updates
        DB::statement("ALTER TABLE purchase_orders DROP CONSTRAINT IF EXISTS purchase_orders_status_check");

        // Then, update any existing records with 'terkirim' status to 'menunggu_persetujuan_supplier'
        DB::statement("UPDATE purchase_orders SET status = 'menunggu_persetujuan_supplier' WHERE status = 'terkirim'");

        // Recreate the constraint with 'menunggu_persetujuan_supplier' instead of 'terkirim'
        DB::statement("
            ALTER TABLE purchase_orders
            ADD CONSTRAINT purchase_orders_status_check
            CHECK (status::varchar(50) = ANY (ARRAY[
                'draft'::varchar(50),
                'dibatalkan_draft'::varchar(50),
                'menunggu_persetujuan_supplier'::varchar(50),
                'perubahan_harga'::varchar(50),
                'dikonfirmasi_supplier'::varchar(50),
                'dikonfirmasi_koperasi'::varchar(50),
                'selesai'::varchar(50),
                'dibatalkan_koperasi'::varchar(50)
            ]))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, drop the existing enum constraint to allow status updates
        DB::statement("ALTER TABLE purchase_orders DROP CONSTRAINT IF EXISTS purchase_orders_status_check");

        // Then, update any records with 'menunggu_persetujuan_supplier' back to 'terkirim'
        DB::statement("UPDATE purchase_orders SET status = 'terkirim' WHERE status = 'menunggu_persetujuan_supplier'");

        // Recreate the constraint with 'terkirim' instead of 'menunggu_persetujuan_supplier'
        DB::statement("
            ALTER TABLE purchase_orders
            ADD CONSTRAINT purchase_orders_status_check
            CHECK (status::varchar(50) = ANY (ARRAY[
                'draft'::varchar(50),
                'dibatalkan_draft'::varchar(50),
                'terkirim'::varchar(50),
                'perubahan_harga'::varchar(50),
                'dikonfirmasi_supplier'::varchar(50),
                'dikonfirmasi_koperasi'::varchar(50),
                'selesai'::varchar(50),
                'dibatalkan_koperasi'::varchar(50)
            ]))
        ");
    }
};
