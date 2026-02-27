<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Shared\Models\BaseModel;

class StockCard extends BaseModel
{
    protected $fillable = [
        'item_id',
        'batch_id',
        'date',
        'type',
        'reference',
        'reference_id',
        'qty_in',
        'qty_out',
        'balance',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'type' => 'string',
        'qty_in' => 'integer',
        'qty_out' => 'integer',
        'balance' => 'integer',
    ];

    /**
     * Relationship: Stock card belongs to item
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(StockItem::class, 'item_id');
    }

    /**
     * Relationship: Stock card belongs to batch
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }

    /**
     * Scope to filter by type
     */
    public function scopeWithType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by reference
     */
    public function scopeWithReference($query, string $reference)
    {
        return $query->where('reference', $reference);
    }
}
