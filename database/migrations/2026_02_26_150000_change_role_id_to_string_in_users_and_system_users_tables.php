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
        // Change role_id in users table from integer to string
        Schema::table('users', function (Blueprint $table) {
            // Add new string column
            $table->string('role_id_new', 20)->nullable()->after('role_id');
        });

        // Convert data: integer to string
        DB::statement("UPDATE users SET role_id_new = CASE
            WHEN role_id = 1 THEN 'SUPER_ADMIN'
            WHEN role_id = 2 THEN 'ADMIN'
            WHEN role_id = 3 THEN 'KEUANGAN'
            WHEN role_id = 4 THEN 'GUDANG'
            WHEN role_id = 5 THEN 'DAPUR'
            WHEN role_id = 6 THEN 'PEMASOK'
            WHEN role_id = 7 THEN 'USER'
            ELSE 'USER'
        END");

        // Drop old column and rename new one
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_id_new', 'role_id');
        });

        // Change role_id in system_users table from integer to string (if exists)
        if (Schema::hasColumn('system_users', 'role_id')) {
            Schema::table('system_users', function (Blueprint $table) {
                // Add new string column
                $table->string('role_id_new', 20)->nullable()->after('role_id');
            });

            // Convert data: integer to string
            DB::statement("UPDATE system_users SET role_id_new = CASE
                WHEN role_id = 1 THEN 'SUPER_ADMIN'
                WHEN role_id = 2 THEN 'ADMIN'
                WHEN role_id = 3 THEN 'KEUANGAN'
                WHEN role_id = 4 THEN 'GUDANG'
                WHEN role_id = 5 THEN 'DAPUR'
                WHEN role_id = 6 THEN 'PEMASOK'
                WHEN role_id = 7 THEN 'USER'
                ELSE 'USER'
            END");

            // Drop old column and rename new one
            Schema::table('system_users', function (Blueprint $table) {
                $table->dropColumn('role_id');
            });

            Schema::table('system_users', function (Blueprint $table) {
                $table->renameColumn('role_id_new', 'role_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert role_id in users table back to integer
        Schema::table('users', function (Blueprint $table) {
            // Add new integer column
            $table->integer('role_id_new')->nullable()->after('role_id');
        });

        // Convert data: string to integer
        DB::statement("UPDATE users SET role_id_new = CASE
            WHEN role_id = 'SUPER_ADMIN' THEN 1
            WHEN role_id = 'ADMIN' THEN 2
            WHEN role_id = 'KEUANGAN' THEN 3
            WHEN role_id = 'GUDANG' THEN 4
            WHEN role_id = 'DAPUR' THEN 5
            WHEN role_id = 'PEMASOK' THEN 6
            WHEN role_id = 'USER' THEN 7
            ELSE 7
        END");

        // Drop old column and rename new one
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_id_new', 'role_id');
        });

        // Revert role_id in system_users table back to integer (if exists)
        if (Schema::hasColumn('system_users', 'role_id')) {
            Schema::table('system_users', function (Blueprint $table) {
                // Add new integer column
                $table->integer('role_id_new')->nullable()->after('role_id');
            });

            // Convert data: string to integer
            DB::statement("UPDATE system_users SET role_id_new = CASE
                WHEN role_id = 'SUPER_ADMIN' THEN 1
                WHEN role_id = 'ADMIN' THEN 2
                WHEN role_id = 'KEUANGAN' THEN 3
                WHEN role_id = 'GUDANG' THEN 4
                WHEN role_id = 'DAPUR' THEN 5
                WHEN role_id = 'PEMASOK' THEN 6
                WHEN role_id = 'USER' THEN 7
                ELSE 7
            END");

            // Drop old column and rename new one
            Schema::table('system_users', function (Blueprint $table) {
                $table->dropColumn('role_id');
            });

            Schema::table('system_users', function (Blueprint $table) {
                $table->renameColumn('role_id_new', 'role_id');
            });
        }
    }
};
