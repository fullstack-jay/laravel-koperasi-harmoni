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
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('qr_string')->unique();
            $table->enum('type', ['KITCHEN_DELIVERY', 'PURCHASE_RECEIPT', 'STOCK_TRANSFER']);
            $table->uuid('reference_id');
            $table->string('reference_type');
            $table->json('data');
            $table->string('image_path');
            $table->timestamp('scanned_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->index(['type', 'reference_id']);
            $table->index('qr_string');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
