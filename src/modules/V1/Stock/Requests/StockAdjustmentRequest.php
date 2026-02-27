<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\V1\Stock\Enums\StockMovementTypeEnum;

/**
 * @OA\Schema(
 *     schema="StockAdjustmentRequest",
 *     title="Stock Adjustment Request",
 *     description="Schema for stock adjustment",
 *     type="object",
 *     required={"item_id", "type", "quantity"},
 *     @OA\Property(property="item_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="batch_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001"),
 *     @OA\Property(property="type", type="string", enum={"in", "out", "adjustment", "opname"}, example="adjustment"),
 *     @OA\Property(property="quantity", type="integer", example=10),
 *     @OA\Property(property="reference", type="string", example="ADJ-001"),
 *     @OA\Property(property="notes", type="string", example="Stock opname adjustment")
 * )
 */
class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_id' => ['required', 'uuid'],
            'batch_id' => ['nullable', 'uuid'],
            'type' => ['required', new Enum(StockMovementTypeEnum::class)],
            'quantity' => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
