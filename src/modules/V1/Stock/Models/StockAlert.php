<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Shared\Models\BaseModel;

class StockAlert extends BaseModel
{
    protected $fillable = [
        'item_id',
        'batch_id',
        'alert_type',
        'severity',
        'message',
        'current_qty',
        'threshold',
        'expiry_date',
        'days_to_expiry',
        'is_resolved',
        'resolved_at',
    ];

    protected $casts = [
        'alert_type' => 'string',
        'severity' => 'string',
        'current_qty' => 'integer',
        'threshold' => 'integer',
        'expiry_date' => 'date',
        'days_to_expiry' => 'integer',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    /**
     * Relationship: Alert belongs to item
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(StockItem::class, 'item_id');
    }

    /**
     * Relationship: Alert belongs to batch
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }

    /**
     * Scope to filter unresolved alerts
     */
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Scope to filter by alert type
     */
    public function scopeWithType($query, string $type)
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Scope to filter by severity
     */
    public function scopeWithSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Mark alert as resolved
     */
    public function markAsResolved(): bool
    {
        $this->is_resolved = true;
        $this->resolved_at = now();

        return $this->save();
    }
}
