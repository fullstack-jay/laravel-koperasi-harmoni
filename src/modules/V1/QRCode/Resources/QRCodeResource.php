<?php

declare(strict_types=1);

namespace Modules\V1\QRCode\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Modules\V1\QRCode\Models\QRCode
 */
class QRCodeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'qr_string' => $this->qr_string,
            'type' => $this->type,
            'reference_id' => $this->reference_id,
            'reference_type' => $this->reference_type,
            'data' => $this->data,
            'image_path' => $this->image_path,
            'image_url' => $this->image_path ? storage_path($this->image_path) : null,
            'scanned_at' => $this->scanned_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'is_active' => $this->is_active,
            'is_valid' => $this->isValid(),
            'is_expired' => $this->isExpired(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
