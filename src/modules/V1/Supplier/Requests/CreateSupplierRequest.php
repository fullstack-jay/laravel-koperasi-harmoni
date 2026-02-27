<?php

declare(strict_types=1);

namespace Modules\V1\Supplier\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\V1\Supplier\Enums\SupplierStatusEnum;
use Modules\V1\Supplier\Models\Supplier;

/**
 * @OA\Schema(
 *     schema="CreateSupplierRequest",
 *     title="Create Supplier Request",
 *     description="Schema for creating a new supplier",
 *     type="object",
 *     required={"name"},
 *     @OA\Property(property="name", type="string", maxLength=255, example="PT Sembako Makmur"),
 *     @OA\Property(property="contact_person", type="string", maxLength=255, example="Budi Santoso"),
 *     @OA\Property(property="phone", type="string", maxLength=20, example="081234567890"),
 *     @OA\Property(property="email", type="string", format="email", example="contact@sembakomakmur.com"),
 *     @OA\Property(property="address", type="string", example="Jl. Raya No. 123, Jakarta"),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}, example="active")
 * )
 */
class CreateSupplierRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'status' => ['nullable', new Enum(SupplierStatusEnum::class)],
        ];
    }
}
