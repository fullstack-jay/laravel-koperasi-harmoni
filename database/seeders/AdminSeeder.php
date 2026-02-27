<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\V1\Admin\Models\Admin;
use Shared\Enums\StatusEnum;
use Shared\Helpers\DateTimeHelper;
use Shared\Helpers\GlobalHelper;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $timestamp = DateTimeHelper::timestamp();

            $admin = Admin::firstOrCreate(
                ['email' => 'admin@gmail.com'],
                [
                    'first_name' => 'Super',
                    'last_name' => 'Admin',
                    'password' => Hash::make(env('ADMIN_DEFAULT_PASSWORD', 'admin123')),
                    'email_verified_at' => now(),
                    'status' => StatusEnum::ACTIVE,
                    'super_admin' => true,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]
            );

            // Assign Super Admin role (role_id = 1)
            $existingRole = DB::table('role_user')
                ->where('user_id', $admin->id)
                ->where('role_id', 1)
                ->first();

            if (!$existingRole) {
                DB::table('role_user')->insert([
                    'user_id' => $admin->id,
                    'role_id' => 1, // Super Admin
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
                $this->command->info('✅ Assigned Super Admin role to admin user');
            }
        });
    }
}
