<?php

declare(strict_types=1);

namespace Modules\V1\Supplier\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\V1\Supplier\Enums\SupplierItemStatusEnum;
use Shared\Models\BaseModel;
use Shared\Traits\HasAuditColumns;

class SupplierItem extends BaseModel
{
    use HasAuditColumns, SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'code',
        'name',
        'category',
        'unit',
        'min_stock',
        'max_stock',
        'buy_price',
        'sell_price',
        'avg_weight',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'avg_weight' => 'decimal:2',
        'min_stock' => 'integer',
        'max_stock' => 'integer',
        'status' => SupplierItemStatusEnum::class,
    ];

    /**
     * Relationship: Item belongs to a supplier
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Relationship: Item created by user
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\Modules\V1\User\Models\User::class, 'created_by');
    }

    /**
     * Relationship: Item updated by user
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(\Modules\V1\User\Models\User::class, 'updated_by');
    }

    /**
     * Scope: Get active items only
     */
    public function scopeActive($query)
    {
        return $query->where('status', SupplierItemStatusEnum::ACTIVE);
    }

    /**
     * Scope: Get items by supplier
     */
    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * Check if item is active
     */
    public function isActive(): bool
    {
        return $this->status === SupplierItemStatusEnum::ACTIVE;
    }

    /**
     * Update sell price (by supplier)
     * This will automatically sync buy_price
     */
    public function updateSellPrice(float $newPrice, ?string $updatedBy = null): bool
    {
        $this->sell_price = $newPrice;
        $this->buy_price = $newPrice; // Auto-sync
        $this->updated_by = $updatedBy;

        return $this->save();
    }
}
