<?php

declare(strict_types=1);

namespace Modules\V1\Supplier\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;

/**
 * @mixin PurchaseOrder
 */
class POUpdateHargaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'poNumber' => $this->po_number,
            'poDate' => $this->po_date?->format('Y-m-d'),
            'supplierId' => $this->supplier_id,
            'supplierName' => $this->whenLoaded('supplier', fn() => $this->supplier->name),
            'status' => $this->status->value,
            'koperasiName' => $this->whenLoaded('createdBy', fn() => $this->createdBy?->full_name ?? ''),
            'koperasiAddress' => $this->whenLoaded('createdBy', fn() => $this->createdBy?->address),
            'items' => POUpdateHargaItemResource::collection($this->whenLoaded('items')),
            'estimatedTotal' => (float) $this->estimated_total,
            'actualTotal' => $this->actual_total ? (float) $this->actual_total : 0,
            'estimatedDeliveryDate' => $this->estimated_delivery_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
