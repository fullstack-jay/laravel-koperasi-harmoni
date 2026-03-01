<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\V1\Stock\Enums\CategoryEnum;

/**
 * @OA\Schema(
 *     schema="StockItemResource",
 *     title="Stock Item Resource",
 *     description="Schema for the stock item resource",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="code", type="string", example="BPO-BRS-PRM-25KG"),
 *     @OA\Property(property="name", type="string", example="Beras Premium"),
 *     @OA\Property(property="category", type="string", example="Bahan Pokok", description="Full category name (translated from code)"),
 *     @OA\Property(property="categoryCode", type="string", example="BPO", description="Category abbreviation code from database"),
 *     @OA\Property(property="unit", type="string", example="kg"),
 *     @OA\Property(property="minStock", type="integer", example=100),
 *     @OA\Property(property="maxStock", type="integer", example=500),
 *     @OA\Property(property="buyPrice", type="number", format="decimal", example=12000.00),
 *     @OA\Property(property="sellPrice", type="number", format="decimal", example=15000.00),
 *     @OA\Property(property="currentStock", type="integer", example=500),
 *     @OA\Property(property="isLowStock", type="boolean"),
 *     @OA\Property(property="isOutOfStock", type="boolean")
 * )
 */
final class StockItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'category' => CategoryEnum::getFullNameByCode($this->category),
            'categoryCode' => $this->category, // Original code from database
            'unit' => $this->unit,
            'minStock' => $this->min_stock,
            'maxStock' => $this->max_stock,
            'buyPrice' => (float) $this->buy_price,
            'sellPrice' => (float) $this->sell_price,
            'currentStock' => $this->current_stock,
            'isLowStock' => $this->isLowStock(),
            'isOutOfStock' => $this->isOutOfStock(),
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
