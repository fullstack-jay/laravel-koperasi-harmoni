<?php

declare(strict_types=1);

namespace Modules\V1\Admin\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\V1\Admin\Models\Admin;
use Modules\V1\Admin\Requests\AdminCreateRequest;
use Modules\V1\Admin\Requests\AdminUpdateRequest;
use Modules\V1\Admin\Resources\AdminResource;
use Modules\V1\User\Models\User;
use Modules\V1\User\Resources\UserResource;
use Modules\V1\User\Services\UserManagementService;
use Shared\Helpers\ActivityHelper;
use Shared\Helpers\ResponseHelper;

final class AdminController extends AdminBaseController
{
    private UserManagementService $userManagementService;

    public function __construct(UserManagementService $userManagementService)
    {
        $this->userManagementService = $userManagementService;
    }

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
     *              @OA\Property(property="sortColumn", type="string", example="created_at", description="Column to sort by (fullName, username, email, role, created_at, id)"),
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
            'fullName' => $sortColumnDir === 'asc'
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
     *              required={"firstName", "username", "email", "password", "role"},
     *
     *              @OA\Property(property="firstName", type="string", example="John", description="First name"),
     *              @OA\Property(property="lastName", type="string", example="Doe", description="Last name (optional)"),
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
     *      @OA\Response(response=422, ref="#/components/responses/422"),
     *      @OA\Response(response=500, ref="#/components/responses/500"),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function store(AdminCreateRequest $request)
    {
        try {
            $user = $this->userManagementService->createUser($request->validated());

            // Log user creation
            ActivityHelper::logUserCreated($user, [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $resource = ($user instanceof Admin)
                ? new AdminResource($user)
                : new UserResource($user);

            return ResponseHelper::success(data: $resource, message: 'Registration successful', status: 201);
        } catch (Exception $e) {
            Log::error("Failed to create user: " . $e->getMessage());

            return ResponseHelper::error($e->getMessage(), 500);
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
     *              required={"firstName", "username", "email", "role"},
     *
     *              @OA\Property(property="firstName", type="string", example="John", description="First name"),
     *              @OA\Property(property="lastName", type="string", example="Doe", description="Last name (optional)"),
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
            $user = $this->userManagementService->updateUser($id, $request->validated());

            // Log user update
            ActivityHelper::logUserUpdated($user, $request->validated(), [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $resource = ($user instanceof Admin)
                ? new AdminResource($user)
                : new UserResource($user);

            return ResponseHelper::success(data: $resource, message: 'User updated successfully');
        } catch (Exception $e) {
            Log::error("Failed to update user: " . $e->getMessage());

            return $e->getMessage() === 'User not found'
                ? ResponseHelper::error('User not found', 404)
                : ResponseHelper::error($e->getMessage(), 500);
        }
    }
}
