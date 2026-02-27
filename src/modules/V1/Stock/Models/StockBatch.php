<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Shared\Models\BaseModel;
use Shared\Traits\HasAuditColumns;

class StockBatch extends BaseModel
{
    use HasAuditColumns;

    protected $fillable = [
        'item_id',
        'batch_number',
        'quantity',
        'remaining_qty',
        'buy_price',
        'expiry_date',
        'location',
        'status',
        'received_date',
        'po_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'buy_price' => 'decimal:2',
        'quantity' => 'integer',
        'remaining_qty' => 'integer',
        'expiry_date' => 'date',
        'received_date' => 'date',
        'status' => 'string',
    ];

    /**
     * Relationship: Batch belongs to stock item
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(StockItem::class, 'item_id');
    }

    /**
     * Relationship: Batch has many stock cards
     */
    public function stockCards(): HasMany
    {
        return $this->hasMany(StockCard::class, 'batch_id');
    }

    /**
     * Relationship: Batch has many alerts
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(StockAlert::class, 'batch_id');
    }

    /**
     * Check if batch is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date->isPast();
    }

    /**
     * Check if batch is expiring soon (within 7 days)
     */
    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->expiry_date->lte(now()->addDays($days));
    }

    /**
     * Check if batch is available
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available' && $this->remaining_qty > 0 && ! $this->isExpired();
    }

    /**
     * Get available quantity
     */
    public function getAvailableQty(): int
    {
        return $this->isAvailable() ? $this->remaining_qty : 0;
    }
}
