<?php

declare(strict_types=1);

namespace Modules\V1\Supplier\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="SupplierResource",
 *     title="Supplier Resource",
 *     description="Schema for the supplier resource",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="code", type="string", example="SUP-001"),
 *     @OA\Property(property="name", type="string", example="PT Sembako Makmur"),
 *     @OA\Property(property="contact_person", type="string", example="Budi Santoso"),
 *     @OA\Property(property="phone", type="string", example="081234567890"),
 *     @OA\Property(property="email", type="string", format="email", example="contact@sembakomakmur.com"),
 *     @OA\Property(property="address", type="string", example="Jl. Raya No. 123, Jakarta"),
 *     @OA\Property(property="status", type="string", example="active")
 * )
 */
final class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'contactPerson' => $this->contact_person,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'status' => $this->status,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
