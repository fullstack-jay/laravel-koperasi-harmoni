<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create roles table
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->bigInteger('created_at')->useCurrent();
                $table->bigInteger('updated_at')->useCurrent();
            });
        }

        // Create permissions table
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->bigInteger('created_at')->useCurrent();
                $table->bigInteger('updated_at')->useCurrent();
            });
        }

        // Create permission_role pivot table (if not exists)
        if (!Schema::hasTable('permission_role')) {
            Schema::create('permission_role', function (Blueprint $table) {
                $table->id();
                $table->foreignId('permission_id')->constrained()->onDelete('cascade');
                $table->foreignId('role_id')->constrained()->onDelete('cascade');
                $table->bigInteger('created_at')->useCurrent();
                $table->bigInteger('updated_at')->useCurrent();
            });
        }

        // Create role_user pivot table
        if (!Schema::hasTable('role_user')) {
            Schema::create('role_user', function (Blueprint $table) {
                $table->id();
                $table->uuid('user_id');
                $table->foreignId('role_id')->constrained()->onDelete('cascade');
                $table->bigInteger('created_at')->useCurrent();
                $table->bigInteger('updated_at')->useCurrent();

                $table->foreign('user_id')->references('id')->on('system_users')->onDelete('cascade');
                $table->unique(['user_id', 'role_id']);
            });
        }

        // Insert default roles only if table is empty
        if (DB::table('roles')->count() === 0) {
            DB::table('roles')->insert([
                [
                    'name' => 'Super Admin',
                    'slug' => 'super_admin',
                    'description' => 'Full system access',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                [
                    'name' => 'Admin',
                    'slug' => 'admin',
                    'description' => 'Administrative access',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                [
                    'name' => 'Keuangan',
                    'slug' => 'keuangan',
                    'description' => 'Finance department access',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                [
                    'name' => 'Gudang',
                    'slug' => 'gudang',
                    'description' => 'Warehouse management access',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                [
                    'name' => 'Dapur',
                    'slug' => 'dapur',
                    'description' => 'Kitchen management access',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                [
                    'name' => 'Procurement',
                    'slug' => 'procurement',
                    'description' => 'Procurement and purchasing access',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
