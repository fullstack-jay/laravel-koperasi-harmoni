<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="StockAlertResource",
 *     title="Stock Alert Resource",
 *     description="Schema for the stock alert resource",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="itemId", type="string", format="uuid"),
 *     @OA\Property(property="batchId", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="alertType", type="string", example="low_stock"),
 *     @OA\Property(property="severity", type="string", example="warning"),
 *     @OA\Property(property="message", type="string", example="Item Beras Premium is below minimum stock level"),
 *     @OA\Property(property="currentQty", type="integer", example=50),
 *     @OA\Property(property="threshold", type="integer", example=100),
 *     @OA\Property(property="expiryDate", type="string", format="date", nullable=true),
 *     @OA\Property(property="daysToExpiry", type="integer", nullable=true),
 *     @OA\Property(property="isResolved", type="boolean"),
 *     @OA\Property(property="resolvedAt", type="string", format="datetime", nullable=true)
 * )
 */
final class StockAlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'itemId' => $this->item_id,
            'batchId' => $this->batch_id,
            'alertType' => $this->alert_type,
            'severity' => $this->severity,
            'message' => $this->message,
            'currentQty' => $this->current_qty,
            'threshold' => $this->threshold,
            'expiryDate' => $this->expiry_date?->format('Y-m-d'),
            'daysToExpiry' => $this->days_to_expiry,
            'isResolved' => $this->is_resolved,
            'resolvedAt' => $this->resolved_at?->format('Y-m-d H:i:s'),
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
