<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'buy_price',
        'sell_price',
        'current_stock',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'min_stock' => 'integer',
        'current_stock' => 'integer',
    ];

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
     * Check if stock is out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->current_stock <= 0;
    }
}
