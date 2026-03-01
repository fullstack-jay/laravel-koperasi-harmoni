<?php

declare(strict_types=1);

namespace Modules\V1\Supplier\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property string $id
 * @property string $item_id
 * @property int $estimated_qty
 * @property int|null $actual_qty
 * @property float $estimated_unit_price
 * @property float|null $actual_unit_price
 * @property float $estimated_subtotal
 * @property float|null $actual_subtotal
 * @property string|null $notes
 * @property \Modules\V1\Stock\Models\StockItem $stockItem
 */
class PengirimanItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'itemId' => $this->item_id,
            'itemName' => $this->stockItem?->name ?? '',
            'estimatedQty' => $this->estimated_qty,
            'receivedQty' => $this->actual_qty ?? 0,
            'estimatedPrice' => (float) $this->estimated_subtotal,
            'actualPrice' => (float) ($this->actual_subtotal ?? 0),
            'unit' => $this->stockItem?->unit ?? '',
            'notes' => $this->notes,
        ];
    }
}
