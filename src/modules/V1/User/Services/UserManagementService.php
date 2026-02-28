<?php

declare(strict_types=1);

namespace Modules\V1\User\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\V1\Admin\Models\Admin;
use Modules\V1\Admin\Models\AdminRole;
use Modules\V1\Kitchen\Services\KitchenService;
use Modules\V1\Supplier\Services\SupplierService;
use Modules\V1\User\Enums\RoleEnum;
use Modules\V1\User\Models\User;
use Shared\Helpers\DateTimeHelper;
use Shared\Helpers\NameHelper;

class UserManagementService
{
    public function __construct(
        private SupplierService $supplierService,
        private KitchenService $kitchenService,
    ) {}

    /**
     * Create a new user (admin or regular)
     *
     * @param array $data
     * @return Admin|User
     * @throws Exception
     */
    public function createUser(array $data)
    {
        return DB::transaction(function () use ($data) {
            $roleEnum = RoleEnum::from($data['role']);

            // Use firstName and lastName directly from request
            $nameParts = [
                'first_name' => $data['firstName'],
                'last_name' => $data['lastName'] ?? '', // default empty string
            ];

            // Handle external entities (Supplier/Dapur)
            $supplierId = $this->handleSupplier($data, $roleEnum, $nameParts);
            $dapurId = $this->handleDapur($data, $roleEnum, $nameParts);

            // Determine model and create user
            $isAdminRole = in_array($roleEnum, [RoleEnum::SUPER_ADMIN, RoleEnum::ADMIN_PEMASOK]);

            if ($isAdminRole) {
                return $this->createAdmin($data, $roleEnum, $nameParts, $supplierId);
            } else {
                return $this->createRegularUser($data, $roleEnum, $nameParts, $supplierId, $dapurId);
            }
        });
    }

    /**
     * Update existing user
     *
     * @param string $id
     * @param array $data
     * @return Admin|User
     * @throws Exception
     */
    public function updateUser(string $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $roleEnum = RoleEnum::from($data['role']);

            // Use firstName and lastName directly from request
            $nameParts = [
                'first_name' => $data['firstName'],
                'last_name' => $data['lastName'] ?? '', // default empty string
            ];

            // Find user in either table
            $user = $this->findUserById($id);
            $userType = $user instanceof Admin ? 'admin' : 'user';

            // Handle external entities (Supplier/Dapur)
            $supplierId = $this->handleSupplierUpdate($data, $roleEnum, $nameParts, $user, $userType);
            $dapurId = $this->handleDapurUpdate($data, $roleEnum, $nameParts, $user, $userType);

            // Update user data
            $updateData = [
                'first_name' => $nameParts['first_name'],
                'last_name' => $nameParts['last_name'],
                'username' => $data['username'],
                'email' => $data['email'],
            ];

            // Add password if provided
            if (!empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            // Add supplier_id for supplier roles
            if ($roleEnum === RoleEnum::ADMIN_PEMASOK && $userType === 'admin' && $supplierId) {
                $updateData['supplier_id'] = $supplierId;
            } elseif ($roleEnum === RoleEnum::PEMASOK && $userType === 'user' && $supplierId) {
                $updateData['supplier_id'] = $supplierId;
            }

            // Add dapur_id for DAPUR role
            if ($roleEnum === RoleEnum::DAPUR && $userType === 'user' && $dapurId) {
                $updateData['dapur_id'] = $dapurId;
            }

            $user->update($updateData);

            // Update role
            $this->syncRole($user, $roleEnum, $userType);

            // Load roles for response
            $user->load(['roles' => function ($q) {
                $q->select('roles.id', 'roles.name', 'roles.slug');
            }]);

            return $user;
        });
    }

    /**
     * Handle supplier creation for new user
     */
    private function handleSupplier(array $data, RoleEnum $roleEnum, array $nameParts): ?string
    {
        if (!in_array($roleEnum, [RoleEnum::ADMIN_PEMASOK, RoleEnum::PEMASOK])) {
            return null;
        }

        // If supplier_id is provided, use existing supplier
        if (!empty($data['supplier_id'])) {
            return $data['supplier_id'];
        }

        // Otherwise, create new supplier
        return $this->supplierService->getOrCreateForUser(
            $data,
            $data['email'],
            $nameParts['first_name']
        );
    }

    /**
     * Handle supplier update
     */
    private function handleSupplierUpdate(array $data, RoleEnum $roleEnum, array $nameParts, $user, string $userType): ?string
    {
        if (!in_array($roleEnum, [RoleEnum::ADMIN_PEMASOK, RoleEnum::PEMASOK])) {
            return null;
        }

        $existingSupplierId = $user->supplier_id ?? null;

        return $this->supplierService->getOrCreateForUser(
            $data,
            $data['email'],
            $nameParts['first_name'],
            $existingSupplierId
        );
    }

    /**
     * Handle dapur creation for new user
     */
    private function handleDapur(array $data, RoleEnum $roleEnum, array $nameParts): ?string
    {
        if ($roleEnum !== RoleEnum::DAPUR) {
            return null;
        }

        // If dapur_id is provided, use existing dapur
        if (!empty($data['dapur_id'])) {
            return $data['dapur_id'];
        }

        // Otherwise, create new dapur
        $fullName = trim($nameParts['first_name'] . ' ' . $nameParts['last_name']);

        return $this->kitchenService->getOrCreateForUser(
            $data,
            $fullName,
            $nameParts['first_name']
        );
    }

    /**
     * Handle dapur update
     */
    private function handleDapurUpdate(array $data, RoleEnum $roleEnum, array $nameParts, $user, string $userType): ?string
    {
        if ($roleEnum !== RoleEnum::DAPUR) {
            return null;
        }

        $existingDapurId = $user->dapur_id ?? null;
        $fullName = trim($nameParts['first_name'] . ' ' . $nameParts['last_name']);

        return $this->kitchenService->getOrCreateForUser(
            $data,
            $fullName,
            $nameParts['first_name'],
            $existingDapurId
        );
    }

    /**
     * Create admin user
     */
    private function createAdmin(array $data, RoleEnum $roleEnum, array $nameParts, ?string $supplierId): Admin
    {
        $adminData = [
            'first_name' => $nameParts['first_name'],
            'last_name' => $nameParts['last_name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ];

        // Add supplier_id for ADMIN_PEMASOK role
        if ($roleEnum === RoleEnum::ADMIN_PEMASOK && $supplierId) {
            $adminData['supplier_id'] = $supplierId;
        }

        $admin = Admin::create($adminData);

        // Attach role
        $this->attachRole($admin, $roleEnum);

        // Mark email as verified
        $admin->markEmailAsVerified();

        // Load roles for response
        $admin->load(['roles' => function ($q) {
            $q->select('roles.id', 'roles.name', 'roles.slug');
        }]);

        return $admin;
    }

    /**
     * Create regular user
     */
    private function createRegularUser(array $data, RoleEnum $roleEnum, array $nameParts, ?string $supplierId, ?string $dapurId): User
    {
        $userData = [
            'first_name' => $nameParts['first_name'],
            'last_name' => $nameParts['last_name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'role_id' => $roleEnum->value,
            'password' => Hash::make($data['password']),
        ];

        // Add supplier_id for PEMASOK role
        if ($roleEnum === RoleEnum::PEMASOK && $supplierId) {
            $userData['supplier_id'] = $supplierId;
        }

        // Add dapur_id for DAPUR role
        if ($roleEnum === RoleEnum::DAPUR && $dapurId) {
            $userData['dapur_id'] = $dapurId;
        }

        $user = User::create($userData);

        // Attach role
        $this->attachRole($user, $roleEnum);

        // Mark email as verified
        $user->markEmailAsVerified();

        // Load roles for response
        $user->load(['roles' => function ($q) {
            $q->select('roles.id', 'roles.name', 'roles.slug');
        }]);

        return $user;
    }

    /**
     * Attach role to user
     */
    private function attachRole(Admin|User $user, RoleEnum $roleEnum): void
    {
        $role = AdminRole::where('slug', $roleEnum->toSlug())->firstOrFail();

        $timestamp = DateTimeHelper::timestamp();
        $user->roles()->attach($role->id, [
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    /**
     * Sync role for user
     */
    private function syncRole(Admin|User $user, RoleEnum $roleEnum, string $userType): void
    {
        $role = AdminRole::where('slug', $roleEnum->toSlug())->firstOrFail();

        $timestamp = DateTimeHelper::timestamp();
        $user->roles()->sync([$role->id => [
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]]);

        // For regular users, also update role_id column
        if ($userType === 'user') {
            $user->update(['role_id' => $roleEnum->value]);
        }
    }

    /**
     * Find user by ID in either table
     */
    private function findUserById(string $id): Admin|User
    {
        $user = Admin::find($id);

        if (!$user) {
            $user = User::find($id);
        }

        if (!$user) {
            throw new Exception('User not found', 404);
        }

        return $user;
    }
}
