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
        Schema::create('surat_jalans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sj_number')->unique();
            $table->uuid('kitchen_order_id');
            $table->uuid('dapur_id');
            $table->date('sj_date');
            $table->string('driver_name')->nullable();
            $table->string('vehicle_plate')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('receiver_name')->nullable();
            $table->text('receiver_notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('kitchen_order_id')->references('id')->on('kitchen_orders')->onDelete('cascade');
            $table->foreign('dapur_id')->references('id')->on('dapurs')->onDelete('restrict');
            $table->index('sj_number');
            $table->index('sj_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_jalans');
    }
};
