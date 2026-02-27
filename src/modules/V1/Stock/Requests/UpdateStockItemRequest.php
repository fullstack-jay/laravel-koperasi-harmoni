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
 *     @OA\Property(property="name", type="string", maxLength=255, example="Beras Premium 5kg"),
 *     @OA\Property(property="category", type="string", maxLength=255, example="Sembako"),
 *     @OA\Property(property="unit", type="string", maxLength=50, example="karung"),
 *     @OA\Property(property="min_stock", type="integer", example=50),
 *     @OA\Property(property="buy_price", type="number", format="decimal", example=13000.00),
 *     @OA\Property(property="sell_price", type="number", format="decimal", example=16000.00)
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
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'buy_price' => ['nullable', 'numeric', 'min:0'],
            'sell_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
