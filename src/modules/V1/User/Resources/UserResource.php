<?php

declare(strict_types=1);

namespace Modules\V1\User\Resources;

use AllowDynamicProperties;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Shared\Helpers\StringHelper;

/**
 * @OA\Schema(
 *     schema="UserResource",
 *     title="User Resource",
 *     description="User resource representation",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", example="john@example.com"),
 *     @OA\Property(property="supplierId", type="string", format="uuid", nullable=true, example=null),
 *     @OA\Property(property="supplierCode", type="string", nullable=true, example="SUP-001"),
 *     @OA\Property(property="namaPerusahaan", type="string", nullable=true, example="PT. Supplier Jaya"),
 *     @OA\Property(property="district", type="string", nullable=true, example="Jakarta Selatan"),
 *     @OA\Property(property="dapurId", type="string", format="uuid", nullable=true, example=null),
 *     @OA\Property(property="dapurCode", type="string", nullable=true, example="DAP-001"),
 *     @OA\Property(property="namaDapur", type="string", nullable=true, example="Dapur Pusat"),
 * )
 */
#[AllowDynamicProperties] final class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Handle email_verified_at - can be timestamp string or DateTime object
        $emailVerifiedAt = null;
        if ($this->email_verified_at) {
            if (is_numeric($this->email_verified_at)) {
                $emailVerifiedAt = date('Y-m-d H:i:s', (int) $this->email_verified_at);
            } else {
                $emailVerifiedAt = $this->email_verified_at;
            }
        }

        // Handle created_at - can be timestamp string or DateTime object
        $createdAt = null;
        if ($this->created_at) {
            if (is_numeric($this->created_at)) {
                $createdAt = date('Y-m-d H:i:s', (int) $this->created_at);
            } else {
                $createdAt = $this->created_at;
            }
        }

        // Get roles relation if loaded (selalu di-load dari controller)
        $roles = $this->whenLoaded('roles') ?? collect();

        // Get primary role from roles relation
        $primaryRole = $roles->isNotEmpty()
            ? $roles->first()->name
            : '-'; // Tanda strip jika tidak ada role

        // Load supplier if supplier_id exists
        $supplierCode = null;
        $namaPerusahaan = null;
        $district = null;
        if ($this->supplier_id) {
            $supplier = \Modules\V1\Supplier\Models\Supplier::find($this->supplier_id);
            $supplierCode = $supplier?->code;
            $namaPerusahaan = $supplier?->name;
            $district = $supplier?->district;
        }

        // Load dapur if dapur_id exists
        $dapurCode = null;
        $namaDapur = null;
        if ($this->dapur_id) {
            $dapur = \Modules\V1\Kitchen\Models\Dapur::find($this->dapur_id);
            $dapurCode = $dapur?->code;
            $namaDapur = $dapur?->name;
        }

        return [
            'no' => $this->row_number ?? null,
            'id' => $this->id,
            'firstName' => StringHelper::toTitleCase($this->first_name ?? ''),
            'lastName' => StringHelper::toTitleCase($this->last_name ?? ''),
            'fullName' => StringHelper::toTitleCase(trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''))),
            'username' => $this->username,
            'email' => $this->email,
            'role' => $primaryRole === '-' ? '-' : StringHelper::toTitleCase($primaryRole),
            'roles' => $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => StringHelper::toTitleCase($role->name),
                    'slug' => $role->slug
                ];
            })->values()->toArray(),
            'supplierId' => $this->supplier_id ?? null,
            'supplierCode' => $supplierCode,
            'namaPerusahaan' => $namaPerusahaan,
            'district' => $district,
            'dapurId' => $this->dapur_id ?? null,
            'dapurCode' => $dapurCode,
            'namaDapur' => $namaDapur,
            'emailVerifiedAt' => $emailVerifiedAt,
            'createdAt' => $createdAt,
        ];
    }
}
