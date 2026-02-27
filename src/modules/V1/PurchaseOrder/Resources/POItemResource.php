<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\V1\PurchaseOrder\Models\PurchaseOrderItem;

/**
 * @mixin PurchaseOrderItem
 */
class POItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'item_name' => $this->whenLoaded('stockItem', fn() => $this->stockItem->name),
            'estimated_unit_price' => (float) $this->estimated_unit_price,
            'estimated_qty' => $this->estimated_qty,
            'estimated_subtotal' => (float) $this->estimated_subtotal,
            'actual_unit_price' => $this->actual_unit_price ? (float) $this->actual_unit_price : null,
            'actual_qty' => $this->actual_qty,
            'actual_subtotal' => $this->actual_subtotal ? (float) $this->actual_subtotal : null,
            'notes' => $this->notes,
        ];
    }
}
