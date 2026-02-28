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
 *     @OA\Property(property="code", type="string", maxLength=255, example="BPO-BRS-PRM-25KG", description="Item code format: CATEGORY-PROD-TYPE[-SIZE]. Category (first part) will be auto-extracted"),
 *     @OA\Property(property="name", type="string", maxLength=255, example="Beras Premium 25kg"),
 *     @OA\Property(property="category", type="string", maxLength=255, example="Sembako", description="Item category (optional, will be auto-extracted from code if not provided)"),
 *     @OA\Property(property="unit", type="string", maxLength=50, example="karung"),
 *     @OA\Property(property="minStock", type="integer", example=50, description="Minimum stock level"),
 *     @OA\Property(property="maxStock", type="integer", example=500, description="Maximum stock level"),
 *     @OA\Property(property="buyPrice", type="number", format="decimal", example=150000.00),
 *     @OA\Property(property="sellPrice", type="number", format="decimal", example=165000.00),
 *     @OA\Property(property="supplierId", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID supplier yang terdaftar")
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
            'minStock' => ['nullable', 'integer', 'min:0', 'lte:maxStock'],
            'maxStock' => ['nullable', 'integer', 'min:0', 'gte:minStock'],
            'buyPrice' => ['nullable', 'numeric', 'min:0'],
            'sellPrice' => ['nullable', 'numeric', 'min:0'],
            'supplierId' => ['nullable', 'uuid', 'exists:suppliers,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Convert camelCase to snake_case for database
        $converted = [];

        if ($this->has('minStock')) {
            $converted['min_stock'] = $this->input('minStock');
        }
        if ($this->has('maxStock')) {
            $converted['max_stock'] = $this->input('maxStock');
        }
        if ($this->has('buyPrice')) {
            $converted['buy_price'] = $this->input('buyPrice');
        }
        if ($this->has('sellPrice')) {
            $converted['sell_price'] = $this->input('sellPrice');
        }
        if ($this->has('supplierId')) {
            $converted['supplier_id'] = $this->input('supplierId');
        }

        $this->merge($converted);
    }
}
