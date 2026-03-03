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
            'itemName' => $this->when(
                $this->relationLoaded('stockItem') && $this->stockItem,
                fn() => $this->stockItem->name
            ),
            'estimatedUnitPrice' => (float) $this->estimated_unit_price,
            'estimatedQty' => $this->estimated_qty,
            'estimatedSubtotal' => (float) $this->estimated_subtotal,
            'actualUnitPrice' => $this->actual_unit_price ? (float) $this->actual_unit_price : null,
            'actualQty' => $this->actual_qty,
            'actualSubtotal' => $this->actual_subtotal ? (float) $this->actual_subtotal : null,
            'notes' => $this->notes,
            // Expired tracking information from stock item
            'expiredInfo' => $this->when(
                $this->relationLoaded('stockItem') && $this->stockItem,
                fn() => $this->getExpiredInfo()
            ),
        ];
    }

    /**
     * Get expired tracking information for this item
     */
    private function getExpiredInfo(): ?array
    {
        if (!$this->stockItem) {
            return null;
        }

        $stockItem = $this->stockItem;

        // If same expiry date
        if ($stockItem->is_same_expired) {
            return [
                'isSameExpiry' => true,
                'expiryDate' => $stockItem->tanggal_expired?->format('Y-m-d'),
                'expiryBatches' => [],
            ];
        }

        // If different expiry dates - get from expiry batches or use summary fields
        $expiryBatches = [];
        if ($this->relationLoaded('expiryBatches')) {
            // Load from PO item's expiry batches relationship
            foreach ($this->expiryBatches as $batch) {
                $expiryBatches[] = [
                    'batchNumber' => $batch->batch_number,
                    'quantity' => $batch->quantity,
                    'expiryDate' => $batch->expiry_date->format('Y-m-d'),
                    'isProcessed' => $batch->is_processed,
                ];
            }
        } elseif ($stockItem->tanggal_expired_terdekat) {
            // Fallback to summary fields if batches not loaded
            $expiryBatches = [
                [
                    'batchNumber' => 1,
                    'quantity' => $stockItem->quantity_expired_terdekat,
                    'expiryDate' => $stockItem->tanggal_expired_terdekat->format('Y-m-d'),
                    'isProcessed' => null,
                ]
            ];

            if ($stockItem->tanggal_expired_terjauh) {
                $expiryBatches[] = [
                    'batchNumber' => 2,
                    'quantity' => $stockItem->quantity_expired_terjauh,
                    'expiryDate' => $stockItem->tanggal_expired_terjauh->format('Y-m-d'),
                    'isProcessed' => null,
                ];
            }
        }

        return [
            'isSameExpiry' => false,
            'expiryDate' => null,
            'expiryBatches' => $expiryBatches,
        ];
    }
}
