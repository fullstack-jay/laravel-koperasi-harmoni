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
            'itemId' => $this->item_id,
            'itemName' => $this->whenLoaded('stockItem', fn() => $this->stockItem->name),
            'estimatedUnitPrice' => (float) $this->estimated_unit_price,
            'estimatedQty' => $this->estimated_qty,
            'estimatedSubtotal' => (float) $this->estimated_subtotal,
            'actualUnitPrice' => $this->actual_unit_price ? (float) $this->actual_unit_price : null,
            'actualQty' => $this->actual_qty,
            'actualSubtotal' => $this->actual_subtotal ? (float) $this->actual_subtotal : null,
            'notes' => $this->notes,
        ];
    }
}
