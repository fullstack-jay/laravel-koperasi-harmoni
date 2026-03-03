<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Shared\Models\BaseModel;
use Shared\Traits\HasAuditColumns;

class StockItem extends BaseModel
{
    use HasAuditColumns, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'category',
        'unit',
        'min_stock',
        'max_stock',
        'buy_price',
        'sell_price',
        'last_price_update_at',
        'current_stock',
        'scheduled_quantity',
        'scheduled_at',
        'scheduled_processed',
        'is_same_expired',
        'tanggal_expired',
        'quantity_expired_terdekat',
        'tanggal_expired_terdekat',
        'quantity_expired_terjauh',
        'tanggal_expired_terjauh',
        'supplier_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'last_price_update_at' => 'datetime',
        'min_stock' => 'integer',
        'max_stock' => 'integer',
        'current_stock' => 'integer',
        'scheduled_quantity' => 'integer',
        'scheduled_at' => 'datetime',
        'scheduled_processed' => 'boolean',
        'is_same_expired' => 'boolean',
        'tanggal_expired' => 'date',
        'quantity_expired_terdekat' => 'integer',
        'tanggal_expired_terdekat' => 'date',
        'quantity_expired_terjauh' => 'integer',
        'tanggal_expired_terjauh' => 'date',
    ];

    /**
     * Relationship: Stock item belongs to supplier
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\Modules\V1\Supplier\Models\Supplier::class);
    }

    /**
     * Relationship: Stock item has many batches
     */
    public function batches(): HasMany
    {
        return $this->hasMany(StockBatch::class, 'item_id');
    }

    /**
     * Relationship: Stock item has many stock cards
     */
    public function stockCards(): HasMany
    {
        return $this->hasMany(StockCard::class, 'item_id');
    }

    /**
     * Relationship: Stock item has many expiry batches
     */
    public function expiryBatches(): HasMany
    {
        return $this->hasMany(StockExpiryBatch::class, 'stock_item_id')
            ->orderBy('expiry_date', 'asc')
            ->orderBy('batch_number', 'asc');
    }

    /**
     * Relationship: Stock item has many alerts
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(StockAlert::class, 'item_id');
    }

    /**
     * Check if stock is below minimum
     */
    public function isLowStock(): bool
    {
        return $this->current_stock < $this->min_stock;
    }

    /**
     * Check if stock is above maximum
     */
    public function isOverStock(): bool
    {
        return $this->current_stock > $this->max_stock;
    }

    /**
     * Check if stock is within optimal range
     */
    public function isOptimalStock(): bool
    {
        return $this->current_stock >= $this->min_stock && $this->current_stock <= $this->max_stock;
    }

    /**
     * Check if stock is out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->current_stock <= 0;
    }

    /**
     * Get expired information for display
     */
    public function getExpiredInfo(): array
    {
        if ($this->is_same_expired) {
            return [
                'type' => 'same',
                'tanggal_expired' => $this->tanggal_expired?->format('d/m/Y'),
                'quantity' => $this->current_stock,
                'message' => "Semua stok expired pada: {$this->tanggal_expired->format('d/m/Y')}",
            ];
        } else {
            return [
                'type' => 'different',
                'terdekat' => [
                    'tanggal' => $this->tanggal_expired_terdekat?->format('d/m/Y'),
                    'quantity' => $this->quantity_expired_terdekat,
                ],
                'terjauh' => [
                    'tanggal' => $this->tanggal_expired_terjauh?->format('d/m/Y'),
                    'quantity' => $this->quantity_expired_terjauh,
                ],
                'message' => "Stok memiliki berbagai tanggal expired (terdekat: {$this->tanggal_expired_terdekat->format('d/m/Y')}, terjauh: {$this->tanggal_expired_terjauh->format('d/m/Y')})",
            ];
        }
    }
}
