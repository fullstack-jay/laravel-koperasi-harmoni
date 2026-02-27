<?php

declare(strict_types=1);

namespace Modules\V1\Kitchen\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\V1\Kitchen\Models\KitchenOrderItem;

/**
 * @mixin KitchenOrderItem
 */
class KitchenOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'item_name' => $this->whenLoaded('stockItem', fn() => $this->stockItem->name),
            'requested_qty' => $this->requested_qty,
            'approved_qty' => $this->approved_qty,
            'unit_price' => (float) $this->unit_price,
            'subtotal' => (float) $this->subtotal,
            'buy_price' => $this->buy_price ? (float) $this->buy_price : null,
            'profit' => $this->profit ? (float) $this->profit : null,
            'notes' => $this->notes,
            'stock_allocations' => $this->stock_allocations,
        ];
    }
}
