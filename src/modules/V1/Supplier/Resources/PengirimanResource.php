<?php

declare(strict_types=1);

namespace Modules\V1\Supplier\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property string $id
 * @property string $po_number
 * @property string $po_date
 * @property string $supplier_id
 * @property string $status
 * @property float $estimated_total
 * @property float|null $actual_total
 * @property string $estimated_delivery_date
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Database\Eloquent\Collection $items
 * @property \Modules\V1\Supplier\Models\Supplier $supplier
 * @property \Modules\V1\Admin\Models\Admin $createdBy
 */
class PengirimanResource extends JsonResource
{
    /**
     * Calculate total based on actualTotal or from items
     * If actualTotal > 0, use actualTotal
     * Otherwise, calculate from items: sum(actualPrice * estimatedQty)
     */
    private function calculateTotal(): float
    {
        // Jika actualTotal > 0, gunakan actualTotal
        if (!empty($this->actual_total) && $this->actual_total > 0) {
            return (float) $this->actual_total;
        }

        // Jika actualTotal = 0, hitung dari items
        $total = 0;
        foreach ($this->items as $item) {
            $total += ($item->actual_unit_price * $item->estimated_qty);
        }

        return (float) $total;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $calculatedTotal = $this->calculateTotal();

        return [
            'id' => $this->id,
            'poNumber' => $this->po_number,
            'poDate' => $this->po_date->format('Y-m-d'),
            'supplierId' => $this->supplier_id,
            'supplierName' => $this->supplier?->name ?? '',
            'status' => $this->status->value,
            'invoiceNumber' => $this->invoice_number,
            'koperasiName' => $this->createdBy?->full_name ?? '',
            'koperasiAddress' => $this->createdBy?->address,
            'items' => PengirimanItemResource::collection($this->items),
            'estimatedTotal' => (float) $this->estimated_total,
            'actualTotal' => (float) ($this->actual_total ?? 0),
            'calculatedTotal' => $calculatedTotal,
            'estimatedDeliveryDate' => $this->estimated_delivery_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'createdAt' => $this->created_at->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
