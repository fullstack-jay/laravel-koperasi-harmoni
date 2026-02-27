<?php

declare(strict_types=1);

namespace Modules\V1\User\Controllers;

use App\Http\Controllers\V1\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\V1\User\Models\User;
use Modules\V1\User\Resources\UserResource;
use Shared\Helpers\ResponseHelper;

final class UserController extends Controller
{
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        // Get parameters with defaults
        $search = $request->input('search', '');
        $sortColumn = $request->input('sortColumn', 'created_at');
        $sortColumnDir = $request->input('sortColumnDir', 'desc');
        $pageNumber = $request->input('pageNumber', 1);
        $pageSize = $request->input('pageSize', 15);

        // Build query with roles
        try {
            $query = User::with(['roles' => function ($q) {
                $q->select('roles.id', 'roles.name', 'roles.slug');
            }]);
        } catch (\Exception $e) {
            // Roles relation doesn't exist
            $query = User::query();
        }

        // Apply global search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ILIKE', "%{$search}%")
                  ->orWhere('last_name', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%")
                  ->orWhere('username', 'ILIKE', "%{$search}%");
            });
        }

        // Apply sorting
        $query->orderBy($sortColumn, $sortColumnDir);

        // Get total count for pagination metadata
        $total = $query->count();

        // Apply pagination with offset/limit
        $offset = ($pageNumber - 1) * $pageSize;
        $users = $query->offset($offset)->limit($pageSize)->get();

        // Add row numbers
        $users->each(function ($user, $index) use ($pageNumber, $pageSize) {
            $user->row_number = ($pageNumber - 1) * $pageSize + $index + 1;
        });

        return ResponseHelper::success(
            UserResource::collection($users),
            meta: [
                'total' => $total,
                'page' => $pageNumber,
                'pageSize' => $pageSize,
                'totalPages' => ceil($total / $pageSize),
            ]
        );
    }

    public function show(string $id): \Illuminate\Http\JsonResponse
    {
        $user = User::findOrFail($id);

        // Load roles if exists
        try {
            $user->load(['roles' => function ($q) {
                $q->select('roles.id', 'roles.name', 'roles.slug');
            }]);
        } catch (\Exception $e) {
            // Roles relation doesn't exist
        }

        return ResponseHelper::success(new UserResource($user));
    }

    /**
     * View user by ID (URL parameter).
     *
     * @OA\Post(
     *      path="/Admin/Users/View/{id}",
     *      summary="View user by ID",
     *      description="Get user details by ID",
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
     *      @OA\Response(
     *          response=200,
     *          description="User retrieved successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="statusCode", type="integer", example=200),
     *              @OA\Property(property="data", ref="#/components/schemas/UserResource")
     *          )
     *      ),
     *
     *      @OA\Response(response=401, ref="#/components/responses/401"),
     *      @OA\Response(response=404, ref="#/components/responses/404"),
     * )
     */
    public function view(string $id): \Illuminate\Http\JsonResponse
    {
        $user = User::findOrFail($id);

        // Load roles if exists
        try {
            $user->load(['roles' => function ($q) {
                $q->select('roles.id', 'roles.name', 'roles.slug');
            }]);
        } catch (\Exception $e) {
            // Roles relation doesn't exist
        }

        return ResponseHelper::success(new UserResource($user));
    }

    public function update(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();

        $user->update([
            'name' => $request->name,
        ]);

        return ResponseHelper::success(data: new UserResource($user), message: 'Profile updated successfully');
    }

    public function changePassword(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ( ! Hash::check($request->input('current_password'), $user->password)) {
            return ResponseHelper::error('Invalid current password', 402);
        }

        $user->update([
            'password' => Hash::make($request->input('new_password')),
        ]);

        return ResponseHelper::success('Password changed successfully');
    }

    /**
     * Delete a user (hard delete).
     *
     * @OA\Post(
     *      path="/Admin/Users/Delete/{id}",
     *      summary="Delete a user",
     *      description="Permanently delete a user by ID from database",
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
     *      @OA\Response(
     *          response=200,
     *          description="User deleted successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="User deleted successfully"),
     *              @OA\Property(property="statusCode", type="integer", example=200)
     *          )
     *      ),
     *
     *      @OA\Response(response=401, ref="#/components/responses/401"),
     *      @OA\Response(response=403, ref="#/components/responses/403"),
     *      @OA\Response(response=404, ref="#/components/responses/404"),
     * )
     */
    public function destroy(string $id): \Illuminate\Http\JsonResponse
    {
        $user = User::findOrFail($id);

        // Prevent deletion of self
        if (auth()->id() === $user->id) {
            return ResponseHelper::error('Cannot delete your own account', 403);
        }

        // Hard delete - permanently remove from database
        $user->forceDelete();

        return ResponseHelper::success(message: 'User deleted successfully');
    }
}
