<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Shared\Models\BaseModel;

class StockExpiryBatch extends BaseModel
{
    protected $fillable = [
        'stock_item_id',
        'batch_number',
        'quantity',
        'expiry_date',
        'is_processed',
        'processed_at',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'is_processed' => 'boolean',
        'processed_at' => 'datetime',
    ];

    /**
     * Relationship: Expiry batch belongs to stock item
     */
    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class, 'stock_item_id');
    }

    /**
     * Check if batch is expiring soon (within 7 days)
     */
    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->expiry_date->lte(now()->addDays($days));
    }

    /**
     * Check if batch is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date->isPast();
    }

    /**
     * Scope: Get unprocessed batches for a specific stock item, ordered by expiry date (FEFO)
     */
    public function scopeUnprocessedForItem($query, $stockItemId)
    {
        return $query->where('stock_item_id', $stockItemId)
            ->where('is_processed', false)
            ->orderBy('expiry_date', 'asc')
            ->orderBy('batch_number', 'asc');
    }

    /**
     * Scope: Get batches sorted by expiry date (FEFO - First Expired First Out)
     */
    public function scopeOrderedByExpiry($query)
    {
        return $query->orderBy('expiry_date', 'asc')
            ->orderBy('batch_number', 'asc');
    }

    /**
     * Mark batch as processed
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'is_processed' => true,
            'processed_at' => now(),
        ]);
    }
}
