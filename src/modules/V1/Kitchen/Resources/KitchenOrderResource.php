<?php

declare(strict_types=1);

namespace Modules\V1\Kitchen\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\V1\Kitchen\Models\KitchenOrder;

/**
 * @mixin KitchenOrder
 */
class KitchenOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'order_date' => $this->order_date,
            'dapur' => $this->whenLoaded('dapur', fn() => [
                'id' => $this->dapur->id,
                'code' => $this->dapur->code,
                'name' => $this->dapur->name,
            ]),
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'estimated_total' => (float) $this->estimated_total,
            'actual_total' => $this->actual_total ? (float) $this->actual_total : null,
            'notes' => $this->notes,
            'sent_at' => $this->sent_at,
            'processed_at' => $this->processed_at,
            'delivered_at' => $this->delivered_at,
            'received_at' => $this->received_at,
            'qr_code' => $this->qr_code,
            'items' => KitchenOrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
