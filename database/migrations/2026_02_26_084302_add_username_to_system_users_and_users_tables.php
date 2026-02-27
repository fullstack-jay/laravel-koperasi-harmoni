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
        // Add username to system_users table (Admin)
        Schema::table('system_users', function (Blueprint $table) {
            if (!Schema::hasColumn('system_users', 'username')) {
                $table->string('username')->unique()->nullable()->after('email');
            }
        });

        // Add username to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique()->nullable()->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_users', function (Blueprint $table) {
            if (Schema::hasColumn('system_users', 'username')) {
                $table->dropUnique('system_users_username_unique');
                $table->dropColumn('username');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                $table->dropUnique('users_username_unique');
                $table->dropColumn('username');
            }
        });
    }
};
