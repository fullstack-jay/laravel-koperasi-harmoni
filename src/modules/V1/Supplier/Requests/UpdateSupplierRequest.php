<?php

declare(strict_types=1);

namespace Modules\V1\Supplier\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\V1\Supplier\Enums\SupplierStatusEnum;

/**
 * @OA\Schema(
 *     schema="UpdateSupplierRequest",
 *     title="Update Supplier Request",
 *     description="Schema for updating an existing supplier",
 *     type="object",
 *     @OA\Property(property="name", type="string", maxLength=255, example="PT Sembako Makmur Updated"),
 *     @OA\Property(property="contact_person", type="string", maxLength=255, example="Ahmad Wijaya"),
 *     @OA\Property(property="phone", type="string", maxLength=20, example="081234567891"),
 *     @OA\Property(property="email", type="string", format="email", example="sales@sembakomakmur.com"),
 *     @OA\Property(property="address", type="string", example="Jl. Raya No. 456, Jakarta"),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}, example="active")
 * )
 */
class UpdateSupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'status' => ['nullable', new Enum(SupplierStatusEnum::class)],
        ];
    }
}
