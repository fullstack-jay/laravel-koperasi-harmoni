<?php

declare(strict_types=1);

namespace Modules\V1\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\V1\User\Enums\RoleEnum;
use Shared\Helpers\DateTimeHelper;
use Shared\Helpers\StringHelper;

/**
 * @OA\Schema(
 *     schema="AdminResource",
 *     title="Admin Resource",
 *     description="Schema for the admin resource",
 *     type="object",
 *
 *     @OA\Property(property="no", type="integer", example=1),
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="firstName", type="string", example="John"),
 *     @OA\Property(property="lastName", type="string", example="Doe"),
 *     @OA\Property(property="fullName", type="string", example="John Doe"),
 *     @OA\Property(property="username", type="string", example="johndoe"),
 *     @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
 *     @OA\Property(property="role", type="string", example="Super Admin"),
 *     @OA\Property(property="roles", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="supplierId", type="string", format="uuid", nullable=true, example=null),
 *     @OA\Property(property="supplierCode", type="string", nullable=true, example="SUP-001"),
 *     @OA\Property(property="status", type="string", example="active"),
 *     @OA\Property(property="isSuperAdmin", type="boolean", example=true),
 *     @OA\Property(property="createdAt", type="string", example="2025-01-15 10:30:00"),
 * )
 */
final class AdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Get primary role name - handle if roles relation doesn't exist
        $roles = $this->whenLoaded('roles') ?? collect();
        $primaryRole = $roles->first()?->name ?? 'User';
        $roleSlug = $roles->first()?->slug ?? null;

        // Load supplier if supplier_id exists
        $supplierCode = null;
        if ($this->supplier_id) {
            $supplier = \Modules\V1\Supplier\Models\Supplier::find($this->supplier_id);
            $supplierCode = $supplier?->code;
        }

        return [
            'no' => $this->row_number ?? null,
            'id' => $this->id,
            'firstName' => StringHelper::toTitleCase($this->first_name),
            'lastName' => StringHelper::toTitleCase($this->last_name),
            'fullName' => StringHelper::toTitleCase($this->first_name . ' ' . $this->last_name),
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->super_admin ? 'Super Admin' : StringHelper::toTitleCase($primaryRole),
            'roles' => $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => StringHelper::toTitleCase($role->name),
                    'slug' => $role->slug,
                ];
            })->values(),
            'supplierId' => $this->supplier_id ?? null,
            'supplierCode' => $supplierCode,
            'status' => $this->status ?? 'active',
            'isSuperAdmin' => (bool) $this->super_admin,
            'createdAt' => DateTimeHelper::dateTime($this->created_at),
        ];
    }
}
