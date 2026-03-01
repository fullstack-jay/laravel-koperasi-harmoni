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
            'poNumber' => $this->po_number,
            'poDate' => $this->po_date?->format('Y-m-d'),
            'supplier' => $this->whenLoaded('supplier', fn() => [
                'id' => $this->supplier->id,
                'code' => $this->supplier->code,
                'name' => $this->supplier->name,
            ]),
            'status' => $this->status->value,
            'statusLabel' => $this->status->getLabel(),
            'estimatedTotal' => (float) $this->estimated_total,
            'actualTotal' => $this->actual_total ? (float) $this->actual_total : null,
            'invoiceNumber' => $this->invoice_number,
            'estimatedDeliveryDate' => $this->estimated_delivery_date?->format('Y-m-d'),
            'actualDeliveryDate' => $this->actual_delivery_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'cancellationReason' => $this->cancellation_reason,
            'sentToSupplierAt' => $this->sent_to_supplier_at?->format('Y-m-d H:i:s'),
            'confirmedBySupplierAt' => $this->confirmed_by_supplier_at?->format('Y-m-d H:i:s'),
            'confirmedByKoperasiAt' => $this->confirmed_by_koperasi_at?->format('Y-m-d H:i:s'),
            'receivedAt' => $this->received_at?->format('Y-m-d H:i:s'),
            'items' => POItemResource::collection($this->whenLoaded('items')),
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
