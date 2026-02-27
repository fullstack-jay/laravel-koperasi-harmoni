<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;

/**
 * @mixin PurchaseOrder
 */
class POResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'po_number' => $this->po_number,
            'po_date' => $this->po_date,
            'supplier' => $this->whenLoaded('supplier', fn() => [
                'id' => $this->supplier->id,
                'code' => $this->supplier->code,
                'name' => $this->supplier->name,
            ]),
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'estimated_total' => (float) $this->estimated_total,
            'actual_total' => $this->actual_total ? (float) $this->actual_total : null,
            'invoice_number' => $this->invoice_number,
            'estimated_delivery_date' => $this->estimated_delivery_date,
            'actual_delivery_date' => $this->actual_delivery_date,
            'notes' => $this->notes,
            'rejection_reason' => $this->rejection_reason,
            'sent_to_supplier_at' => $this->sent_to_supplier_at,
            'confirmed_by_supplier_at' => $this->confirmed_by_supplier_at,
            'confirmed_by_koperasi_at' => $this->confirmed_by_koperasi_at,
            'received_at' => $this->received_at,
            'items' => POItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
