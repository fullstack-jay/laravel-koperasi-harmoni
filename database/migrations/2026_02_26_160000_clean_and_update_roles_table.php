<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Delete all existing roles
        DB::table('roles')->delete();

        // Get current timestamp as Unix timestamp
        $now = time();

        // Insert only the 6 required roles
        $roles = [
            [
                'id' => 1,
                'name' => 'Super Admin',
                'slug' => 'super_admin',
                'description' => 'Super Administrator with full access',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'Admin Pemasok',
                'slug' => 'admin_pemasok',
                'description' => 'Administrator for supplier management',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'Keuangan',
                'slug' => 'keuangan',
                'description' => 'Finance department role',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'name' => 'Pemasok',
                'slug' => 'pemasok',
                'description' => 'Supplier role',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 5,
                'name' => 'Koperasi',
                'slug' => 'koperasi',
                'description' => 'Koperasi role',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 6,
                'name' => 'Dapur',
                'slug' => 'dapur',
                'description' => 'Kitchen department role',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('roles')->insert($roles);

        // Delete all role_user entries
        DB::table('role_user')->delete();

        // Update users table: convert old role_id strings to new ones
        DB::statement("UPDATE users SET role_id = CASE
            WHEN role_id IN ('SUPER_ADMIN', 'ADMIN', 'USER') THEN 'SUPER_ADMIN'
            WHEN role_id = 'KEUANGAN' THEN 'KEUANGAN'
            WHEN role_id IN ('SUPPLIER', 'PEMASOK', 'PROCUREMENT') THEN 'PEMASOK'
            WHEN role_id = 'KOPERASI' THEN 'KOPERASI'
            WHEN role_id = 'DAPUR' THEN 'DAPUR'
            WHEN role_id = 'GUDANG' THEN 'KEUANGAN'
            ELSE 'PEMASOK'
        END");

        // Re-assign admin to Super Admin role
        $adminId = DB::table('system_users')->where('email', 'admin@gmail.com')->value('id');
        if ($adminId) {
            $now = time();
            DB::table('role_user')->insert([
                'user_id' => $adminId,
                'role_id' => 1, // Super Admin
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Re-assign supplier to Pemasok role
        $supplierUserId = DB::table('users')->where('email', 'supplier@gmail.com')->value('id');
        if ($supplierUserId) {
            $now = time();
            DB::table('role_user')->insert([
                'user_id' => $supplierUserId,
                'role_id' => 4, // Pemasok
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete role_user assignments created in this migration
        $adminId = DB::table('system_users')->where('email', 'admin@gmail.com')->value('id');
        if ($adminId) {
            DB::table('role_user')
                ->where('user_id', $adminId)
                ->where('role_id', 1)
                ->delete();
        }

        $supplierUserId = DB::table('users')->where('email', 'supplier@gmail.com')->value('id');
        if ($supplierUserId) {
            DB::table('role_user')
                ->where('user_id', $supplierUserId)
                ->where('role_id', 4)
                ->delete();
        }

        // Delete the 6 roles inserted in this migration
        DB::table('roles')->whereIn('id', [1, 2, 3, 4, 5, 6])->delete();

        // Note: The users table role_id updates cannot be easily reverted
        // because the original values were lost. You may need to restore
        // from backup or re-seed the database after rollback.
    }
};
