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
    /**
     * Calculate total price based on actual or estimated unit price
     * Uses actual_price if available (and not zero), otherwise falls back to estimated_price
     */
    private function calculateTotal(): float
    {
        $total = 0;

        foreach ($this->items as $item) {
            // Gunakan actual_unit_price jika ada (dan bukan 0), jika tidak gunakan estimated_unit_price
            $price = $item->actual_unit_price ?? $item->estimated_unit_price;

            // Kalikan dengan estimated_qty
            $total += ($price * $item->estimated_qty);
        }

        return (float) $total;
    }

    public function toArray(Request $request): array
    {
        $calculatedTotal = $this->calculateTotal();

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
            'calculatedTotal' => $calculatedTotal,
            'estimatedDeliveryDate' => $this->estimated_delivery_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
