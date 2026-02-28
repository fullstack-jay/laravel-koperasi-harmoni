<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="UpdateStockItemRequest",
 *     title="Update Stock Item Request",
 *     description="Schema for updating an existing stock item",
 *     type="object",
 *     @OA\Property(property="code", type="string", maxLength=255, example="STK-001", description="Unique item code/SKU"),
 *     @OA\Property(property="name", type="string", maxLength=255, example="Beras Premium 25kg"),
 *     @OA\Property(property="category", type="string", maxLength=255, example="Sembako"),
 *     @OA\Property(property="unit", type="string", maxLength=50, example="karung"),
 *     @OA\Property(property="min_stock", type="integer", example=50, description="Minimum stock level"),
 *     @OA\Property(property="max_stock", type="integer", example=500, description="Maximum stock level"),
 *     @OA\Property(property="buy_price", type="number", format="decimal", example=150000.00),
 *     @OA\Property(property="sell_price", type="number", format="decimal", example=165000.00),
 *     @OA\Property(property="supplier_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID supplier yang terdaftar")
 * )
 */
class UpdateStockItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $stockItemId = $this->route('id'); // Get the stock item ID from route

        return [
            'code' => ['nullable', 'string', 'max:255', 'unique:stock_items,code,'.$stockItemId],
            'name' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'min_stock' => ['nullable', 'integer', 'min:0', 'lte:max_stock'],
            'max_stock' => ['nullable', 'integer', 'min:0', 'gte:min_stock'],
            'buy_price' => ['nullable', 'numeric', 'min:0'],
            'sell_price' => ['nullable', 'numeric', 'min:0'],
            'supplier_id' => ['nullable', 'uuid', 'exists:suppliers,id'],
        ];
    }
}
