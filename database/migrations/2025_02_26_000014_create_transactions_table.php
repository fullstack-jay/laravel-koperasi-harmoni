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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->enum('type', ['purchase', 'sales']);
            $table->enum('category', ['po', 'kitchen_order', 'adjustment']);
            $table->decimal('amount', 10, 2);
            $table->decimal('profit', 10, 2)->nullable();
            $table->decimal('margin', 5, 2)->nullable();
            $table->string('reference');
            $table->uuid('reference_id');
            $table->uuid('supplier_id')->nullable();
            $table->uuid('dapur_id')->nullable();
            $table->json('items');
            $table->enum('payment_status', ['pending', 'paid', 'overdue'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['date', 'type']);
            $table->index('reference');
            $table->index('type');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
