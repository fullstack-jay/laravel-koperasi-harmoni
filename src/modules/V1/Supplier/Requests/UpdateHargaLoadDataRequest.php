<?php

declare(strict_types=1);

namespace Modules\V1\Supplier\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="UpdateHargaLoadDataRequest",
 *     title="Update Harga Load Data Request",
 *     description="Schema for loading purchase orders data for price update",
 *     type="object",
 *     @OA\Property(property="pageNumber", type="integer", example=1, description="Page number"),
 *     @OA\Property(property="pageSize", type="integer", example=10, description="Items per page"),
 *     @OA\Property(property="sortColumn", type="string", example="id", description="Column to sort by"),
 *     @OA\Property(property="sortColumnDir", type="string", enum={"ASC", "DESC"}, example="ASC", description="Sort direction"),
 *     @OA\Property(property="search", type="string", example="", description="Search string"),
 *     @OA\Property(property="supplierId", type="string", format="uuid", example="a12ee051-b01a-4e65-bc83-42594da0f757", description="Supplier ID (required for super_admin, optional for supplier role)")
 * )
 */
class UpdateHargaLoadDataRequest extends FormRequest
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
            'pageNumber' => ['nullable', 'integer', 'min:1'],
            'pageSize' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sortColumn' => ['nullable', 'string'],
            'sortColumnDir' => ['nullable', 'string', 'in:ASC,DESC'],
            'search' => ['nullable', 'string'],
            'supplierId' => ['nullable', 'uuid', 'exists:suppliers,id'],
        ];
    }
}
