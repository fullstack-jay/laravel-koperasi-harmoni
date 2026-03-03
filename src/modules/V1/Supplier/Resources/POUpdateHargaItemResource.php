<?php

declare(strict_types=1);

namespace Modules\V1\Supplier\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\V1\PurchaseOrder\Models\PurchaseOrderItem;

/**
 * @mixin PurchaseOrderItem
 */
class POUpdateHargaItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'itemId' => $this->item_id,
            'itemName' => $this->whenLoaded('stockItem', fn() => $this->stockItem->name),
            'category' => $this->whenLoaded('stockItem', fn() => $this->stockItem->category),
            'estimatedQty' => $this->estimated_qty,
            'receivedQty' => $this->actual_qty ?? 0,
            'estimatedPrice' => (float) $this->estimated_unit_price,
            'actualPrice' => $this->actual_unit_price ? (float) $this->actual_unit_price : (float) $this->estimated_unit_price,
            'unit' => $this->whenLoaded('stockItem', fn() => $this->stockItem->unit),
            'notes' => $this->notes,
        ];
    }
}
