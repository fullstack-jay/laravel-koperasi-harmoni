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
 *     required={"name"},
 *     @OA\Property(property="name", type="string", maxLength=255, example="Beras Premium"),
 *     @OA\Property(property="category", type="string", maxLength=255, example="Sembako"),
 *     @OA\Property(property="unit", type="string", maxLength=50, example="kg"),
 *     @OA\Property(property="min_stock", type="integer", example=100),
 *     @OA\Property(property="buy_price", type="number", format="decimal", example=12000.00),
 *     @OA\Property(property="sell_price", type="number", format="decimal", example=15000.00)
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
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'buy_price' => ['nullable', 'numeric', 'min:0'],
            'sell_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
