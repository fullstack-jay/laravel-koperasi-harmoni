<?php

declare(strict_types=1);

namespace Modules\V1\QRCode\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Shared\Models\BaseModel;

class QRCode extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'qr_string',
        'type',
        'reference_id',
        'reference_type',
        'data',
        'image_path',
        'scanned_at',
        'expires_at',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'data' => 'array',
        'scanned_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function reference()
    {
        return $this->morphTo();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }
}
