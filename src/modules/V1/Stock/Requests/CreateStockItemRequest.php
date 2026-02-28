<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\V1\Stock\Rules\UniqueStockItemCodeKey;

/**
 * @OA\Schema(
 *     schema="CreateStockItemRequest",
 *     title="Create Stock Item Request",
 *     description="Schema for creating a new stock item",
 *     type="object",
 *     required={"code", "name", "minStock", "maxStock", "buyPrice", "sellPrice", "supplierId"},
 *     @OA\Property(property="code", type="string", maxLength=255, example="BPO-BRS-PRM-25KG", description="Item code format: CATEGORY-PROD-TYPE[-SIZE]. Product-type combination must be unique. Category (first part) will be auto-extracted and saved to category field"),
 *     @OA\Property(property="name", type="string", maxLength=255, example="Beras Premium 25kg", description="Item name (must be unique)"),
 *     @OA\Property(property="unit", type="string", maxLength=50, example="karung"),
 *     @OA\Property(property="minStock", type="integer", example=50, description="Minimum stock level for alerts"),
 *     @OA\Property(property="maxStock", type="integer", example=500, description="Maximum stock level for overstock alerts"),
 *     @OA\Property(property="buyPrice", type="number", format="decimal", example=150000.00),
 *     @OA\Property(property="sellPrice", type="number", format="decimal", example=165000.00),
 *     @OA\Property(property="supplierId", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID supplier yang terdaftar")
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
            'code' => ['required', 'string', 'max:255', new UniqueStockItemCodeKey],
            'name' => ['required', 'string', 'max:255', 'unique:stock_items,name'],
            'unit' => ['nullable', 'string', 'max:50'],
            'minStock' => ['required', 'integer', 'min:0', 'lte:maxStock'],
            'maxStock' => ['required', 'integer', 'min:0', 'gte:minStock'],
            'buyPrice' => ['required', 'numeric', 'min:0'],
            'sellPrice' => ['required', 'numeric', 'min:0'],
            'supplierId' => ['required', 'uuid', 'exists:suppliers,id'],
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
