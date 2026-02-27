<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="StockBatchResource",
 *     title="Stock Batch Resource",
 *     description="Schema for the stock batch resource",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="itemId", type="string", format="uuid"),
 *     @OA\Property(property="batchNumber", type="string", example="BATCH-20250226-STK001-001"),
 *     @OA\Property(property="quantity", type="integer", example=100),
 *     @OA\Property(property="remainingQty", type="integer", example=75),
 *     @OA\Property(property="buyPrice", type="number", format="decimal", example=12000.00),
 *     @OA\Property(property="expiryDate", type="string", format="date", example="2025-12-31"),
 *     @OA\Property(property="location", type="string", example="Gudang A-Rak 1"),
 *     @OA\Property(property="status", type="string", example="available"),
 *     @OA\Property(property="receivedDate", type="string", format="date", example="2025-02-26"),
 *     @OA\Property(property="isExpired", type="boolean"),
 *     @OA\Property(property="isExpiringSoon", type="boolean"),
 *     @OA\Property(property="isAvailable", type="boolean")
 * )
 */
final class StockBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'itemId' => $this->item_id,
            'batchNumber' => $this->batch_number,
            'quantity' => $this->quantity,
            'remainingQty' => $this->remaining_qty,
            'buyPrice' => (float) $this->buy_price,
            'expiryDate' => $this->expiry_date?->format('Y-m-d'),
            'location' => $this->location,
            'status' => $this->status,
            'receivedDate' => $this->received_date?->format('Y-m-d'),
            'isExpired' => $this->isExpired(),
            'isExpiringSoon' => $this->isExpiringSoon(),
            'isAvailable' => $this->isAvailable(),
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
