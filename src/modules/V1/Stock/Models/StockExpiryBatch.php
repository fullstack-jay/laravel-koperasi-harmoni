<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Shared\Models\BaseModel;

class StockExpiryBatch extends BaseModel
{
    protected $fillable = [
        'purchase_order_item_id',
        'purchase_order_id',
        'supplier_id',
        'stock_item_id',
        'item_name',
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
     * Relationship: Expiry batch belongs to purchase order item
     */
    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(\Modules\V1\PurchaseOrder\Models\PurchaseOrderItem::class, 'purchase_order_item_id');
    }

    /**
     * Relationship: Expiry batch belongs to purchase order
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(\Modules\V1\PurchaseOrder\Models\PurchaseOrder::class, 'purchase_order_id');
    }

    /**
     * Relationship: Expiry batch belongs to supplier
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\Modules\V1\Supplier\Models\Supplier::class, 'supplier_id');
    }

    /**
     * Relationship: Expiry batch belongs to stock item (optional)
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
     * Scope: Get unprocessed batches for a specific PO item, ordered by expiry date (FEFO)
     */
    public function scopeUnprocessedForPOItem($query, $poItemId)
    {
        return $query->where('purchase_order_item_id', $poItemId)
            ->where('is_processed', false)
            ->orderBy('expiry_date', 'asc')
            ->orderBy('batch_number', 'asc');
    }

    /**
     * Scope: Get batches for a specific PO
     */
    public function scopeForPO($query, $poId)
    {
        return $query->where('purchase_order_id', $poId);
    }

    /**
     * Scope: Get batches for a specific supplier
     */
    public function scopeForSupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
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
