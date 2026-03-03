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
        'condition', // good, damaged, near_expired
        'received_date',
        'po_id',
        'supplier_id',
        'supplier_invoice_no',
        'batch_notes',
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
        'condition' => 'string',
    ];

    /**
     * Relationship: Batch belongs to stock item
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(StockItem::class, 'item_id');
    }

    /**
     * Relationship: Batch belongs to supplier
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\Modules\V1\Supplier\Models\Supplier::class, 'supplier_id');
    }

    /**
     * Relationship: Batch belongs to purchase order
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(\Modules\V1\PurchaseOrder\Models\PurchaseOrder::class, 'po_id');
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

    /**
     * Check if batch is in good condition
     */
    public function isGoodCondition(): bool
    {
        return $this->condition === 'good';
    }

    /**
     * Get batch trace information
     * Returns array with tracing details
     */
    public function getTraceInfo(): array
    {
        return [
            'batch_number' => $this->batch_number,
            'po_number' => $this->purchaseOrder?->po_number,
            'po_date' => $this->purchaseOrder?->po_date?->format('d/m/Y'),
            'supplier_name' => $this->supplier?->name,
            'supplier_invoice' => $this->supplier_invoice_no,
            'received_date' => $this->received_date?->format('d/m/Y'),
            'expiry_date' => $this->expiry_date?->format('d/m/Y'),
            'buy_price' => $this->buy_price,
            'condition' => $this->condition,
            'location' => $this->location,
            'qty_original' => $this->quantity,
            'qty_remaining' => $this->remaining_qty,
            'qty_sold' => $this->quantity - $this->remaining_qty,
            'days_until_expiry' => $this->expiry_date?->diffInDays(now(), false),
            'is_expired' => $this->isExpired(),
            'is_expiring_soon' => $this->isExpiringSoon(),
        ];
    }

    /**
     * Scope: Get available batches for specific item (FEFO sorted)
     */
    public function scopeAvailableForItem($query, $itemId)
    {
        return $query->where('item_id', $itemId)
            ->where('status', 'available')
            ->where('remaining_qty', '>', 0)
            ->where('condition', 'good')
            ->where('expiry_date', '>=', now())
            ->orderBy('expiry_date', 'asc') // FEFO: expired dulu
            ->orderBy('received_date', 'asc'); // Jika sama expiry, ambil yang diterima dulu
    }
}
