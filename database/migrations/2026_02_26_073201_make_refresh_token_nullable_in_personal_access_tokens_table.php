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
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Drop unique constraint first
            $table->dropUnique('personal_access_tokens_refresh_token_unique');

            // Make nullable
            $table->string('refresh_token', 64)->nullable()->change();
            $table->timestamp('refresh_token_expires_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->string('refresh_token', 64)->nullable(false)->change();
            $table->timestamp('refresh_token_expires_at')->nullable(false)->change();

            // Recreate unique constraint
            $table->unique('refresh_token');
        });
    }
};
