<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="CreateStockItemRequest",
 *     title="Create Stock Item Request",
 *     description="Schema for creating a new stock item",
 *     type="object",
 *     required={"code", "name", "supplier_id"},
 *     @OA\Property(property="code", type="string", maxLength=255, example="STK-001", description="Unique item code/SKU"),
 *     @OA\Property(property="name", type="string", maxLength=255, example="Beras Premium 25kg"),
 *     @OA\Property(property="category", type="string", maxLength=255, example="Sembako"),
 *     @OA\Property(property="unit", type="string", maxLength=50, example="karung"),
 *     @OA\Property(property="min_stock", type="integer", example=50, description="Minimum stock level for alerts"),
 *     @OA\Property(property="max_stock", type="integer", example=500, description="Maximum stock level for overstock alerts"),
 *     @OA\Property(property="buy_price", type="number", format="decimal", example=150000.00),
 *     @OA\Property(property="sell_price", type="number", format="decimal", example=165000.00),
 *     @OA\Property(property="supplier_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID supplier yang terdaftar")
 * )
 */
class CreateStockItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', 'unique:stock_items,code'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'min_stock' => ['required', 'integer', 'min:0', 'lte:max_stock'],
            'max_stock' => ['required', 'integer', 'min:0', 'gte:min_stock'],
            'buy_price' => ['required', 'numeric', 'min:0'],
            'sell_price' => ['required', 'numeric', 'min:0'],
            'supplier_id' => ['required', 'uuid', 'exists:suppliers,id'],
        ];
    }
}
