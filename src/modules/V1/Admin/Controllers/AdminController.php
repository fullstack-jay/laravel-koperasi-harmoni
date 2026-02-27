<?php

declare(strict_types=1);

namespace Modules\V1\Admin\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\V1\Admin\Models\Admin;
use Modules\V1\Admin\Models\AdminRole;
use Modules\V1\User\Enums\RoleEnum;
use Modules\V1\User\Models\User;
use Modules\V1\Admin\Requests\AdminCreateRequest;
use Modules\V1\Admin\Requests\AdminUpdateRequest;
use Modules\V1\Admin\Resources\AdminResource;
use Modules\V1\User\Models\Role;
use Modules\V1\Supplier\Models\Supplier;
use Modules\V1\Kitchen\Models\Dapur;
use Shared\Helpers\DateTimeHelper;
use Shared\Helpers\ResponseHelper;

final class AdminController extends AdminBaseController
{
    /**
     * @OA\Post(
     *      path="/Admin/Users/LoadData",
     *      summary="Get all users",
     *      description="Returns a paginated list of admins with search and sorting capabilities. Includes no, name, username, role, email, supplier_id, and created_at.",
     *      tags={"Admins"},
     *
     *      @OA\RequestBody(
     *          required=false,
     *          description="Optional parameters for filtering and pagination",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="search", type="string", example="john", description="Global search across name, email, and username"),
     *              @OA\Property(property="sortColumn", type="string", example="created_at", description="Column to sort by (namaLengkap, username, email, role, created_at, id)"),
     *              @OA\Property(property="sortColumnDir", type="string", enum={"asc", "desc"}, example="desc", description="Sort direction"),
     *              @OA\Property(property="pageNumber", type="integer", example=1, description="Page number"),
     *              @OA\Property(property="pageSize", type="integer", example=15, description="Items per page"),
     *              @OA\Property(property="role", type="string", example="PEMASOK", description="Filter by role (slug or name, case-insensitive)"),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Data retrieved successfully"),
     *              @OA\Property(property="statusCode", type="integer", example=200),
     *              @OA\Property(property="data", type="array", @OA\Items(
     *                  type="object",
     *                  @OA\Property(property="no", type="integer", example=1),
     *                  @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                  @OA\Property(property="firstName", type="string", example="John"),
     *                  @OA\Property(property="lastName", type="string", example="Doe"),
     *                  @OA\Property(property="fullName", type="string", example="John Doe"),
     *                  @OA\Property(property="username", type="string", example="johndoe"),
     *                  @OA\Property(property="email", type="string", example="john@example.com"),
     *                  @OA\Property(property="role", type="string", example="Super Admin"),
     *                  @OA\Property(property="roles", type="array", @OA\Items(
     *                      type="object",
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="name", type="string", example="Super Admin"),
     *                      @OA\Property(property="slug", type="string", example="super_admin"),
     *                  )),
     *                  @OA\Property(property="supplierId", type="string", nullable=true, example=null),
     *                  @OA\Property(property="status", type="string", example="active"),
     *                  @OA\Property(property="isSuperAdmin", type="boolean", example=true),
     *                  @OA\Property(property="createdAt", type="string", example="2025-01-15 10:30:00"),
     *              )),
     *              @OA\Property(property="meta", type="object",
     *                  @OA\Property(property="total", type="integer", example=50),
     *                  @OA\Property(property="page", type="integer", example=1),
     *                  @OA\Property(property="pageSize", type="integer", example=15),
     *                  @OA\Property(property="totalPages", type="integer", example=4),
     *              )
     *          )
     *      ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function index(Request $request)
    {
        // Get parameters with defaults
        $search = $request->input('search', '');
        $sortColumn = $request->input('sortColumn', 'created_at');
        $sortColumnDir = $request->input('sortColumnDir', 'desc');
        $pageNumber = $request->input('pageNumber', 1);
        $pageSize = $request->input('pageSize', 15);
        $roleFilter = $request->input('role', ''); // Role filter

        // Get data from both tables
        $admins = collect();
        $users = collect();

        // Try to load admins with roles if the table exists
        try {
            $adminQuery = \Modules\V1\Admin\Models\Admin::with(['roles' => function ($q) {
                $q->select('roles.id', 'roles.name', 'roles.slug');
            }]);

            if (!empty($search)) {
                $adminQuery->where(function ($q) use ($search) {
                    $q->where('first_name', 'ILIKE', "%{$search}%")
                      ->orWhere('last_name', 'ILIKE', "%{$search}%")
                      ->orWhere('email', 'ILIKE', "%{$search}%")
                      ->orWhere('username', 'ILIKE', "%{$search}%");
                });
            }

            $admins = $adminQuery->get();
        } catch (\Exception $e) {
            // Roles table doesn't exist, load without roles
            $adminQuery = \Modules\V1\Admin\Models\Admin::query();

            if (!empty($search)) {
                $adminQuery->where(function ($q) use ($search) {
                    $q->where('first_name', 'ILIKE', "%{$search}%")
                      ->orWhere('last_name', 'ILIKE', "%{$search}%")
                      ->orWhere('email', 'ILIKE', "%{$search}%")
                      ->orWhere('username', 'ILIKE', "%{$search}%");
                });
            }

            $admins = $adminQuery->get();
        }

        // Get regular users (suppliers, koperasi, etc.)
        // Try to load users with roles if the table exists
        try {
            $userQuery = \Modules\V1\User\Models\User::with(['roles' => function ($q) {
                $q->select('roles.id', 'roles.name', 'roles.slug');
            }]);

            if (!empty($search)) {
                $userQuery->where(function ($q) use ($search) {
                    $q->where('first_name', 'ILIKE', "%{$search}%")
                      ->orWhere('last_name', 'ILIKE', "%{$search}%")
                      ->orWhere('email', 'ILIKE', "%{$search}%")
                      ->orWhere('username', 'ILIKE', "%{$search}%");
                });
            }

            $users = $userQuery->get();
        } catch (\Exception $e) {
            // Roles table doesn't exist, load without roles
            $userQuery = \Modules\V1\User\Models\User::query();

            if (!empty($search)) {
                $userQuery->where(function ($q) use ($search) {
                    $q->where('first_name', 'ILIKE', "%{$search}%")
                      ->orWhere('last_name', 'ILIKE', "%{$search}%")
                      ->orWhere('email', 'ILIKE', "%{$search}%")
                      ->orWhere('username', 'ILIKE', "%{$search}%");
                });
            }

            $users = $userQuery->get();
        }

        // Merge both collections and add type indicator
        $allUsers = $admins->map(function ($admin) {
            $admin->user_type = 'admin';
            return $admin;
        })->merge($users->map(function ($user) {
            $user->user_type = 'user';
            return $user;
        }));

        // Apply role filter if provided
        if (!empty($roleFilter)) {
            $allUsers = $allUsers->filter(function ($user) use ($roleFilter) {
                $roles = $user->roles ?? collect();
                $roleSlug = $roles->first()?->slug ?? '';
                $roleName = $roles->first()?->name ?? '';

                // Match by role slug or role name (case-insensitive)
                return strtolower($roleSlug) === strtolower($roleFilter)
                    || strtolower($roleName) === strtolower($roleFilter);
            })->values();
        }

        // Add static row numbers BEFORE sorting (based on creation order)
        $allUsers->each(function ($user, $index) {
            $user->static_row_number = $index + 1;
        });

        // Apply sorting
        $allUsers = match ($sortColumn) {
            'namaLengkap' => $sortColumnDir === 'asc'
                ? $allUsers->sortBy(fn ($user) => strtolower($user->first_name . ' ' . $user->last_name))
                : $allUsers->sortByDesc(fn ($user) => strtolower($user->first_name . ' ' . $user->last_name)),
            'username' => $sortColumnDir === 'asc'
                ? $allUsers->sortBy(fn ($user) => strtolower($user->username ?? ''))
                : $allUsers->sortByDesc(fn ($user) => strtolower($user->username ?? '')),
            'email' => $sortColumnDir === 'asc'
                ? $allUsers->sortBy(fn ($user) => strtolower($user->email ?? ''))
                : $allUsers->sortByDesc(fn ($user) => strtolower($user->email ?? '')),
            'role' => $sortColumnDir === 'asc'
                ? $allUsers->sortBy(fn ($user) => strtolower(($user->roles ?? collect())->first()?->name ?? ''))
                : $allUsers->sortByDesc(fn ($user) => strtolower(($user->roles ?? collect())->first()?->name ?? '')),
            'created_at' => $sortColumnDir === 'asc'
                ? $allUsers->sortBy('created_at')
                : $allUsers->sortByDesc('created_at'),
            'id' => $sortColumnDir === 'asc'
                ? $allUsers->sortBy('id')
                : $allUsers->sortByDesc('id'),
            default => $allUsers->sortByDesc('created_at'),
        };

        $allUsers = $allUsers->values();

        // Get total count
        $total = $allUsers->count();

        // Apply pagination
        $offset = ($pageNumber - 1) * $pageSize;
        $paginatedUsers = $allUsers->slice($offset, $pageSize)->values();

        // Use static row number (doesn't change with sorting)
        $paginatedUsers->each(function ($user) {
            $user->row_number = $user->static_row_number;
        });

        // Transform to resources
        $data = $paginatedUsers->map(function ($user) {
            if ($user->user_type === 'admin') {
                return new \Modules\V1\Admin\Resources\AdminResource($user);
            } else {
                return new \Modules\V1\User\Resources\UserResource($user);
            }
        });

        return ResponseHelper::success(
            $data,
            meta: [
                'total' => $total,
                'page' => $pageNumber,
                'pageSize' => $pageSize,
                'totalPages' => ceil($total / $pageSize),
            ]
        );
    }

    public function show(Admin $admin)
    {
        try {
            $admin->load(['roles' => function ($q) {
                $q->select('roles.id', 'roles.name', 'roles.slug');
            }]);
        } catch (\Exception $e) {
            // Roles table doesn't exist, continue without loading roles
        }

        return ResponseHelper::success(new AdminResource($admin));
    }

    /**
     * @OA\Post(
     *      path="/Admin/Users/Create",
     *      summary="Create a new user",
     *      description="Create a new user (admin or regular user) based on role. SUPER_ADMIN and ADMIN_PEMASOK will be created in system_users table. Other roles will be created in users table.",
     *      tags={"Admins"},
     *
     *      @OA\RequestBody(
     *          required=true,
     *
     *          @OA\JsonContent(
     *              required={"namaLengkap", "username", "email", "password", "role"},
     *
     *              @OA\Property(property="namaLengkap", type="string", example="John Doe", description="Full name"),
     *              @OA\Property(property="username", type="string", example="johndoe", description="Username"),
     *              @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Email address"),
     *              @OA\Property(property="password", type="string", minLength=8, example="password123", description="Password"),
     *              @OA\Property(
     *                  property="role",
     *                  type="string",
     *                  enum={"SUPER_ADMIN", "ADMIN_PEMASOK", "KEUANGAN", "PEMASOK", "KOPERASI", "DAPUR"},
     *                  example="PEMASOK",
     *                  description="Role to assign to the user"
     *              ),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=201,
     *          description="User created successfully",
     *
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="Registration successful"),
     *              @OA\Property(property="statusCode", type="integer", example=201),
     *              @OA\Property(property="data", ref="#/components/schemas/UserResource")
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=209,
     *          description="Account already exists",
     *
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="error"),
     *              @OA\Property(property="message", type="string", example="Account already exists"),
     *              @OA\Property(property="statusCode", type="integer", example=209)
     *          )
     *      ),
     *
     *      @OA\Response(response=422, ref="#/components/responses/422"),
     *      @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function store(AdminCreateRequest $request)
    {
        // Check if user already exists in either table
        $emailExists = Admin::where('email', $request->email)->exists() || User::where('email', $request->email)->exists();
        $usernameExists = Admin::where('username', $request->username)->exists() || User::where('username', $request->username)->exists();

        if ($emailExists || $usernameExists) {
            return ResponseHelper::error('Account already exists', 209);
        }

        try {
            DB::beginTransaction();

            // Split namaLengkap into first_name and last_name
            $namaLengkap = trim($request->namaLengkap);
            $nameParts = explode(' ', $namaLengkap, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            // Get role enum
            $roleEnum = RoleEnum::from($request->role);

            // Determine user type based on role
            $isAdminRole = in_array($roleEnum, [RoleEnum::SUPER_ADMIN, RoleEnum::ADMIN_PEMASOK]);

            // Handle supplier creation for ADMIN_PEMASOK and PEMASOK roles
            $supplierId = null;
            if ($roleEnum === RoleEnum::ADMIN_PEMASOK || $roleEnum === RoleEnum::PEMASOK) {
                if ($request->filled('supplier_id')) {
                    // Use existing supplier
                    $supplierId = $request->supplier_id;
                } else {
                    // Get supplier data from either nested (supplier_data.*) or root level fields
                    $category = $request->input('supplier_data.supplier_category') ?? $request->input('supplierKategori');
                    $companyCode = $request->input('supplier_data.supplier_company_code') ?? $request->input('supplierKodePerusahaan');
                    $location = $request->input('supplier_data.supplier_location') ?? $request->input('supplierLokasi');
                    $year = $request->input('supplier_data.supplier_year') ?? $request->input('supplierTahun');
                    $sequence = $request->input('supplier_data.supplier_sequence') ?? $request->input('supplierUrut');

                    // Parse full supplier code if provided in format: SUP-{TIPE}-{KODE PERUSAHAAN}-{KODE DAERAH}-{TAHUN}-{NOMOR URUT}
                    $fullSupplierCode = $request->input('supplier_data.supplier_code');
                    if (!empty($fullSupplierCode) && strpos($fullSupplierCode, 'SUP-') === 0 && empty($category)) {
                        $parts = explode('-', $fullSupplierCode);
                        if (count($parts) === 6) {
                            $category = $category ?: ($parts[1] ?? '');
                            $companyCode = $companyCode ?: ($parts[2] ?? '');
                            $location = $location ?: ($parts[3] ?? '');
                            $year = $year ?: ($parts[4] ?? '');
                            $sequence = $sequence ?: ($parts[5] ?? '');
                        }
                    }

                    // Assemble supplier_code from separate components if provided, otherwise use full code
                    if ($category && $companyCode && $location && $year && $sequence) {
                        // Assemble from separate components: SUP-{TIPE}-{KODE PERUSAHAAN}-{KODE DAERAH}-{TAHUN}-{NOMOR URUT}
                        $supplierCode = 'SUP-' . $category . '-' . $companyCode . '-' . $location . '-' . $year . '-' . $sequence;
                    } else {
                        // Use full supplier_code directly
                        $supplierCode = $fullSupplierCode ?? $request->input('supplier_data.supplier_code');
                    }

                    $supplierType = $request->input('supplier_data.supplier_type') ?? $request->input('supplierKategori');
                    $district = $request->input('supplier_data.district') ?? $request->input('supplierDistrict');
                    $companyName = $request->input('supplier_data.name') ?? $request->input('supplierNama');
                    $contactPerson = $request->input('supplier_data.contact_person') ?? $request->input('supplierContactPerson', $firstName);
                    $phone = $request->input('supplier_data.phone') ?? $request->input('supplierPhone');
                    $supplierEmail = $request->input('supplier_data.email') ?? $request->input('supplierEmail', $request->email);
                    $address = $request->input('supplier_data.address') ?? $request->input('supplierAddress');

                    $supplier = Supplier::create([
                        'code' => $supplierCode,
                        'name' => $companyName,
                        'contact_person' => $contactPerson,
                        'phone' => $phone,
                        'email' => $supplierEmail,
                        'address' => $address,
                        'district' => $district,
                        'supplier_type' => $supplierType,
                        'status' => 'active',
                        'created_by' => null,
                        'updated_by' => null,
                    ]);

                    $supplierId = $supplier->id;
                }
            }

            // Handle dapur creation for DAPUR role
            $dapurId = null;
            if ($roleEnum === RoleEnum::DAPUR) {
                if ($request->filled('dapur_id')) {
                    // Use existing dapur
                    $dapurId = $request->dapur_id;
                } else {
                    // Get dapur data from either nested (dapur_data.*) or root level fields
                    $fullDapurCode = $request->input('dapur_data.dapur_code') ?? $request->input('dapurKode');
                    $dapurZona = $request->input('dapur_data.dapur_zona') ?? $request->input('dapurZona');
                    $dapurTahun = $request->input('dapur_data.dapur_year') ?? $request->input('dapurTahun');
                    $dapurUrut = $request->input('dapur_data.dapur_sequence') ?? $request->input('dapurUrut');

                    // Use full code directly if it's in correct format
                    if (!empty($fullDapurCode) && strpos($fullDapurCode, 'DPR-') === 0) {
                        $dapurFullCode = $fullDapurCode;
                    } elseif (!empty($fullDapurCode) && !empty($dapurZona) && !empty($dapurTahun) && !empty($dapurUrut)) {
                        // Assemble from components
                        $dapurFullCode = 'DPR-' . $fullDapurCode . '-' . $dapurZona . '-' . $dapurTahun . '-' . $dapurUrut;
                    } else {
                        // Fallback to auto-generated code
                        $lastDapur = Dapur::withTrashed()->orderBy('code', 'desc')->first();
                        $lastNumber = $lastDapur ? (int)str_replace('DAP-', '', $lastDapur->code) : 0;
                        $dapurFullCode = 'DAP-' . str_pad((string)($lastNumber + 1), 3, '0', STR_PAD_LEFT);
                    }

                    $dapurName = $request->input('dapur_data.name') ?? $request->input('dapurNama', $firstName . ' ' . $lastName);
                    $dapurLocation = $request->input('dapur_data.location') ?? $request->input('dapurLocation');
                    $dapurPicName = $request->input('dapur_data.pic_name') ?? $request->input('dapurPicName', $firstName);
                    $dapurPicPhone = $request->input('dapur_data.pic_phone') ?? $request->input('dapurPicPhone');

                    $dapur = Dapur::create([
                        'code' => $dapurFullCode,
                        'name' => $dapurName,
                        'location' => $dapurLocation,
                        'pic_name' => $dapurPicName,
                        'pic_phone' => $dapurPicPhone,
                        'status' => 'active',
                        'created_by' => null,
                        'updated_by' => null,
                    ]);

                    $dapurId = $dapur->id;
                }
            }

            if ($isAdminRole) {
                // Prepare admin data
                $adminData = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'username' => $request->username,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ];

                // Add supplier_id for ADMIN_PEMASOK role
                if ($roleEnum === RoleEnum::ADMIN_PEMASOK && $supplierId) {
                    $adminData['supplier_id'] = $supplierId;
                }

                // Create admin user in system_users table
                $admin = Admin::create($adminData);

                // Get role from database using slug
                $role = AdminRole::where('slug', $roleEnum->toSlug())->firstOrFail();

                // Attach role with timestamps
                $timestamp = DateTimeHelper::timestamp();
                $admin->roles()->attach($role->id, [
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

                // Mark email as verified
                $admin->markEmailAsVerified();

                // Load roles for response
                $admin->load(['roles' => function ($q) {
                    $q->select('roles.id', 'roles.name', 'roles.slug');
                }]);

                $resource = new \Modules\V1\Admin\Resources\AdminResource($admin);
            } else {
                // Prepare user data
                $userData = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'username' => $request->username,
                    'email' => $request->email,
                    'role_id' => $roleEnum->value,
                    'password' => Hash::make($request->password),
                ];

                // Add supplier_id for PEMASOK role (already created above)
                if ($roleEnum === RoleEnum::PEMASOK && $supplierId) {
                    $userData['supplier_id'] = $supplierId;
                }

                // Add dapur_id for DAPUR role (already created above)
                if ($roleEnum === RoleEnum::DAPUR && $dapurId) {
                    $userData['dapur_id'] = $dapurId;
                }

                // Create regular user in users table
                $user = User::create($userData);

                // Mark email as verified
                $user->markEmailAsVerified();

                // Get role from database using slug and attach to pivot table
                $role = AdminRole::where('slug', $roleEnum->toSlug())->firstOrFail();
                $timestamp = DateTimeHelper::timestamp();
                $user->roles()->attach($role->id, [
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

                // Load roles for response
                $user->load(['roles' => function ($q) {
                    $q->select('roles.id', 'roles.name', 'roles.slug');
                }]);

                $resource = new \Modules\V1\User\Resources\UserResource($user);
            }

            DB::commit();

            return ResponseHelper::success(data: $resource, message: 'Registration successful', status: 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);

            return ResponseHelper::error();
        }
    }

    /**
     * @OA\Post(
     *      path="/Admin/Users/Update/{id}",
     *      summary="Update a user",
     *      description="Update user information (admin or regular user) by ID",
     *      tags={"Admins"},
     *      security={{"bearerAuth": {}}},
     *
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="User ID",
     *          @OA\Schema(type="string", format="uuid")
     *      ),
     *
     *      @OA\RequestBody(
     *          required=true,
     *
     *          @OA\JsonContent(
     *              required={"namaLengkap", "username", "email", "role"},
     *
     *              @OA\Property(property="namaLengkap", type="string", example="John Doe", description="Full name"),
     *              @OA\Property(property="username", type="string", example="johndoe", description="Username"),
     *              @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Email address"),
     *              @OA\Property(property="password", type="string", minLength=8, example="password123", description="Password (optional)"),
     *              @OA\Property(
     *                  property="role",
     *                  type="string",
     *                  enum={"SUPER_ADMIN", "ADMIN_PEMASOK", "KEUANGAN", "PEMASOK", "KOPERASI", "DAPUR"},
     *                  example="PEMASOK"
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="User updated successfully",
     *
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="User updated successfully"),
     *              @OA\Property(property="statusCode", type="integer", example=200)
     *          )
     *      ),
     *
     *      @OA\Response(response=401, ref="#/components/responses/401"),
     *      @OA\Response(response=404, ref="#/components/responses/404"),
     *      @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function update(AdminUpdateRequest $request, string $id)
    {
        try {
            DB::beginTransaction();

            // Split namaLengkap into first_name and last_name
            $namaLengkap = trim($request->namaLengkap);
            $nameParts = explode(' ', $namaLengkap, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            // Get role enum
            $roleEnum = RoleEnum::from($request->role);

            // Determine user type based on role
            $isAdminRole = in_array($roleEnum, [RoleEnum::SUPER_ADMIN, RoleEnum::ADMIN_PEMASOK]);

            // Find user in either table
            $user = Admin::find($id);
            $userType = $user ? 'admin' : null;

            if (!$user) {
                $user = User::find($id);
                $userType = $user ? 'user' : null;
            }

            if (!$user) {
                return ResponseHelper::error('User not found', 404);
            }

            // Handle supplier creation/retrieval
            $supplierId = null;
            if ($roleEnum === RoleEnum::ADMIN_PEMASOK || $roleEnum === RoleEnum::PEMASOK) {
                if ($request->filled('supplier_id')) {
                    // Use different existing supplier
                    $supplierId = $request->supplier_id;
                } elseif ($user->supplier_id) {
                    // User has existing supplier - check if we need to update it
                    $hasSupplierData = $request->filled('supplier_data.name')
                        || $request->filled('supplier_data.supplier_code')
                        || $request->filled('supplierKategori')
                        || $request->filled('supplierNama');

                    if ($hasSupplierData) {
                        // Update existing supplier
                        $supplier = Supplier::find($user->supplier_id);

                        if ($supplier) {
                            // Get supplier data from either nested or root level fields
                            $category = $request->input('supplier_data.supplier_category') ?? $request->input('supplierKategori');
                            $companyCode = $request->input('supplier_data.supplier_company_code') ?? $request->input('supplierKodePerusahaan');
                            $location = $request->input('supplier_data.supplier_location') ?? $request->input('supplierLokasi');
                            $year = $request->input('supplier_data.supplier_year') ?? $request->input('supplierTahun');
                            $sequence = $request->input('supplier_data.supplier_sequence') ?? $request->input('supplierUrut');

                            // Assemble supplier_code from separate components if provided
                            if ($category && $companyCode && $location && $year && $sequence) {
                                $supplierCode = 'SUP-' . $category . '-' . $companyCode . '-' . $location . '-' . $year . '-' . $sequence;
                                $supplier->code = $supplierCode;
                            } elseif ($request->filled('supplier_data.supplier_code')) {
                                $supplier->code = $request->input('supplier_data.supplier_code');
                            }

                            // Update other fields if provided
                            if ($request->filled('supplier_data.supplier_type') || $request->filled('supplierKategori')) {
                                $supplier->supplier_type = $request->input('supplier_data.supplier_type') ?? $request->input('supplierKategori');
                            }
                            if ($request->filled('supplier_data.district') || $request->filled('supplierDistrict')) {
                                $supplier->district = $request->input('supplier_data.district') ?? $request->input('supplierDistrict');
                            }
                            if ($request->filled('supplier_data.name') || $request->filled('supplierNama')) {
                                $supplier->name = $request->input('supplier_data.name') ?? $request->input('supplierNama');
                            }
                            if ($request->filled('supplier_data.contact_person') || $request->filled('supplierContactPerson')) {
                                $supplier->contact_person = $request->input('supplier_data.contact_person') ?? $request->input('supplierContactPerson');
                            }
                            if ($request->filled('supplier_data.phone') || $request->filled('supplierPhone')) {
                                $supplier->phone = $request->input('supplier_data.phone') ?? $request->input('supplierPhone');
                            }
                            if ($request->filled('supplier_data.email') || $request->filled('supplierEmail')) {
                                $supplier->email = $request->input('supplier_data.email') ?? $request->input('supplierEmail');
                            }
                            if ($request->filled('supplier_data.address') || $request->filled('supplierAddress')) {
                                $supplier->address = $request->input('supplier_data.address') ?? $request->input('supplierAddress');
                            }

                            $supplier->save();
                        }

                        $supplierId = $user->supplier_id;
                    } else {
                        // Keep existing supplier without changes
                        $supplierId = $user->supplier_id;
                    }
                } else {
                    // Create new supplier from supplier_data
                    $category = $request->input('supplier_data.supplier_category') ?? $request->input('supplierKategori');
                    $companyCode = $request->input('supplier_data.supplier_company_code') ?? $request->input('supplierKodePerusahaan');
                    $location = $request->input('supplier_data.supplier_location') ?? $request->input('supplierLokasi');
                    $year = $request->input('supplier_data.supplier_year') ?? $request->input('supplierTahun');
                    $sequence = $request->input('supplier_data.supplier_sequence') ?? $request->input('supplierUrut');

                    // Assemble supplier_code from separate components if provided, otherwise use full code
                    if ($category && $companyCode && $location && $year && $sequence) {
                        // Assemble from separate components: SUP-{TIPE}-{KODE PERUSAHAAN}-{KODE DAERAH}-{TAHUN}-{NOMOR URUT}
                        $supplierCode = 'SUP-' . $category . '-' . $companyCode . '-' . $location . '-' . $year . '-' . $sequence;
                    } else {
                        // Use full supplier_code directly
                        $supplierCode = $request->input('supplier_data.supplier_code');
                    }

                    $supplierType = $request->input('supplier_data.supplier_type') ?? $request->input('supplierKategori');
                    $district = $request->input('supplier_data.district') ?? $request->input('supplierDistrict');
                    $companyName = $request->input('supplier_data.name') ?? $request->input('supplierNama');
                    $contactPerson = $request->input('supplier_data.contact_person') ?? $request->input('supplierContactPerson', $firstName);
                    $phone = $request->input('supplier_data.phone') ?? $request->input('supplierPhone');
                    $supplierEmail = $request->input('supplier_data.email') ?? $request->input('supplierEmail', $request->email);
                    $address = $request->input('supplier_data.address') ?? $request->input('supplierAddress');

                    // Only create if supplier code is provided
                    if ($supplierCode) {
                        $supplier = Supplier::create([
                            'code' => $supplierCode,
                            'name' => $companyName,
                            'contact_person' => $contactPerson,
                            'phone' => $phone,
                            'email' => $supplierEmail,
                            'address' => $address,
                            'district' => $district,
                            'supplier_type' => $supplierType,
                            'status' => 'active',
                            'created_by' => null,
                            'updated_by' => null,
                        ]);

                        $supplierId = $supplier->id;
                    }
                }
            }

            // Handle dapur creation/retrieval
            $dapurId = null;
            if ($roleEnum === RoleEnum::DAPUR) {
                if ($request->filled('dapur_id')) {
                    // Use different existing dapur
                    $dapurId = $request->dapur_id;
                } elseif ($user->dapur_id) {
                    // User has existing dapur - check if we need to update it
                    $hasDapurData = $request->filled('dapur_data.name')
                        || $request->filled('dapurNama');

                    if ($hasDapurData) {
                        // Update existing dapur
                        $dapur = Dapur::find($user->dapur_id);

                        if ($dapur) {
                            // Get dapur data from either nested or root level fields
                            $fullDapurCode = $request->input('dapur_data.dapur_code') ?? $request->input('dapurKode');
                            $dapurZona = $request->input('dapur_data.dapur_zona') ?? $request->input('dapurZona');
                            $dapurTahun = $request->input('dapur_data.dapur_year') ?? $request->input('dapurTahun');
                            $dapurUrut = $request->input('dapur_data.dapur_sequence') ?? $request->input('dapurUrut');

                            // Use full code directly if it's in correct format
                            if (!empty($fullDapurCode) && strpos($fullDapurCode, 'DPR-') === 0) {
                                $dapur->code = $fullDapurCode;
                            } elseif (!empty($fullDapurCode) && !empty($dapurZona) && !empty($dapurTahun) && !empty($dapurUrut)) {
                                // Assemble from components
                                $dapurFullCode = 'DPR-' . $fullDapurCode . '-' . $dapurZona . '-' . $dapurTahun . '-' . $dapurUrut;
                                $dapur->code = $dapurFullCode;
                            }

                            // Update other fields if provided
                            if ($request->filled('dapur_data.name') || $request->filled('dapurNama')) {
                                $dapur->name = $request->input('dapur_data.name') ?? $request->input('dapurNama');
                            }
                            if ($request->filled('dapur_data.location') || $request->filled('dapurLocation')) {
                                $dapur->location = $request->input('dapur_data.location') ?? $request->input('dapurLocation');
                            }
                            if ($request->filled('dapur_data.pic_name') || $request->filled('dapurPicName')) {
                                $dapur->pic_name = $request->input('dapur_data.pic_name') ?? $request->input('dapurPicName');
                            }
                            if ($request->filled('dapur_data.pic_phone') || $request->filled('dapurPicPhone')) {
                                $dapur->pic_phone = $request->input('dapur_data.pic_phone') ?? $request->input('dapurPicPhone');
                            }

                            $dapur->save();
                        }

                        $dapurId = $user->dapur_id;
                    } else {
                        // Keep existing dapur without changes
                        $dapurId = $user->dapur_id;
                    }
                } else {
                    // Create new dapur from dapur_data
                    $fullDapurCode = $request->input('dapur_data.dapur_code') ?? $request->input('dapurKode');
                    $dapurZona = $request->input('dapur_data.dapur_zona') ?? $request->input('dapurZona');
                    $dapurTahun = $request->input('dapur_data.dapur_year') ?? $request->input('dapurTahun');
                    $dapurUrut = $request->input('dapur_data.dapur_sequence') ?? $request->input('dapurUrut');

                    // Use full code directly if it's in correct format
                    if (!empty($fullDapurCode) && strpos($fullDapurCode, 'DPR-') === 0) {
                        $dapurFullCode = $fullDapurCode;
                    } elseif (!empty($fullDapurCode) && !empty($dapurZona) && !empty($dapurTahun) && !empty($dapurUrut)) {
                        // Assemble from components
                        $dapurFullCode = 'DPR-' . $fullDapurCode . '-' . $dapurZona . '-' . $dapurTahun . '-' . $dapurUrut;
                    } else {
                        // Fallback to auto-generated code
                        $lastDapur = Dapur::withTrashed()->orderBy('code', 'desc')->first();
                        $lastNumber = $lastDapur ? (int)str_replace('DAP-', '', $lastDapur->code) : 0;
                        $dapurFullCode = 'DAP-' . str_pad((string)($lastNumber + 1), 3, '0', STR_PAD_LEFT);
                    }

                    $dapurName = $request->input('dapur_data.name') ?? $request->input('dapurNama');
                    $dapurLocation = $request->input('dapur_data.location') ?? $request->input('dapurLocation');
                    $dapurPicName = $request->input('dapur_data.pic_name') ?? $request->input('dapurPicName', $firstName);
                    $dapurPicPhone = $request->input('dapur_data.pic_phone') ?? $request->input('dapurPicPhone');

                    $dapur = Dapur::create([
                        'code' => $dapurFullCode,
                        'name' => $dapurName,
                        'location' => $dapurLocation,
                        'pic_name' => $dapurPicName,
                        'pic_phone' => $dapurPicPhone,
                        'status' => 'active',
                        'created_by' => null,
                        'updated_by' => null,
                    ]);

                    $dapurId = $dapur->id;
                }
            }

            // Prepare update data
            $updateData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'username' => $request->username,
                'email' => $request->email,
            ];

            // Add password if provided
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            // Add supplier_id for ADMIN_PEMASOK (admin) or PEMASOK (user) role
            if ($roleEnum === RoleEnum::ADMIN_PEMASOK && $userType === 'admin' && $supplierId) {
                $updateData['supplier_id'] = $supplierId;
            } elseif ($roleEnum === RoleEnum::PEMASOK && $userType === 'user' && $supplierId) {
                $updateData['supplier_id'] = $supplierId;
            }

            // Add dapur_id for DAPUR role
            if ($roleEnum === RoleEnum::DAPUR && $userType === 'user' && $dapurId) {
                $updateData['dapur_id'] = $dapurId;
            }

            // Update user
            $user->update($updateData);

            // Update role
            $role = AdminRole::where('slug', $roleEnum->toSlug())->firstOrFail();

            // Sync role in pivot table with Unix timestamp
            $timestamp = DateTimeHelper::timestamp();
            $user->roles()->sync([$role->id => [
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]]);

            // For regular users, also update role_id column
            if ($userType === 'user') {
                $user->update(['role_id' => $roleEnum->value]);
            }

            // Load roles for response
            $user->load(['roles' => function ($q) {
                $q->select('roles.id', 'roles.name', 'roles.slug');
            }]);

            DB::commit();

            // Create appropriate resource based on user type
            $resource = $userType === 'admin'
                ? new \Modules\V1\Admin\Resources\AdminResource($user)
                : new \Modules\V1\User\Resources\UserResource($user);

            return ResponseHelper::success(data: $resource, message: 'User updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);

            return ResponseHelper::error();
        }
    }
}
