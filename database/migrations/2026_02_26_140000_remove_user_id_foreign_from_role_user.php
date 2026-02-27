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
        // Drop foreign key constraint to allow both system_users and users tables
        Schema::table('role_user', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add foreign key constraint
        Schema::table('role_user', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('system_users')->onDelete('cascade');
        });
    }
};
