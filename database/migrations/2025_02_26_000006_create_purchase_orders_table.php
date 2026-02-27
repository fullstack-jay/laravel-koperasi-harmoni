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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('po_number')->unique();
            $table->date('po_date');
            $table->uuid('supplier_id');
            $table->enum('status', ['draft', 'terkirim', 'dikonfirmasi_supplier', 'dikonfirmasi_koperasi', 'selesai', 'dibatalkan'])->default('draft');
            $table->decimal('estimated_total', 10, 2)->default(0);
            $table->decimal('actual_total', 10, 2)->nullable();
            $table->string('invoice_number')->nullable();
            $table->date('estimated_delivery_date');
            $table->date('actual_delivery_date')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('sent_to_supplier_at')->nullable();
            $table->timestamp('confirmed_by_supplier_at')->nullable();
            $table->timestamp('confirmed_by_koperasi_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('restrict');
            $table->index(['supplier_id', 'status']);
            $table->index('po_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
