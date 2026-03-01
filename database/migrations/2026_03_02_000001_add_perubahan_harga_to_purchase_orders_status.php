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
        // Drop the existing enum constraint and recreate with 'perubahan_harga' included
        DB::statement("ALTER TABLE purchase_orders DROP CONSTRAINT purchase_orders_status_check");
        DB::statement("ALTER TABLE purchase_orders ALTER COLUMN status TYPE varchar(50) USING status::varchar(50)");
        DB::statement("ALTER TABLE purchase_orders ADD CONSTRAINT purchase_orders_status_check CHECK (status::varchar(50) = ANY (ARRAY['draft'::varchar(50), 'terkirim'::varchar(50), 'perubahan_harga'::varchar(50), 'dikonfirmasi_supplier'::varchar(50), 'dikonfirmasi_koperasi'::varchar(50), 'selesai'::varchar(50), 'dibatalkan'::varchar(50)]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original constraint without 'perubahan_harga'
        DB::statement("ALTER TABLE purchase_orders DROP CONSTRAINT purchase_orders_status_check");
        DB::statement("ALTER TABLE purchase_orders ADD CONSTRAINT purchase_orders_status_check CHECK (status::varchar(50) = ANY (ARRAY['draft'::varchar(50), 'terkirim'::varchar(50), 'dikonfirmasi_supplier'::varchar(50), 'dikonfirmasi_koperasi'::varchar(50), 'selesai'::varchar(50), 'dibatalkan'::varchar(50)]))");
    }
};
