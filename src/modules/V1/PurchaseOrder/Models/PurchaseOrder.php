<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\V1\PurchaseOrder\Enums\POStatusEnum;
use Modules\V1\Supplier\Models\Supplier;
use Shared\Models\BaseModel;

class PurchaseOrder extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'po_number',
        'po_date',
        'supplier_id',
        'status',
        'estimated_total',
        'confirmed_total',
        'actual_total',
        'invoice_number',
        'estimated_delivery_date',
        'actual_delivery_date',
        'notes',
        'rejection_reason',
        'sent_to_supplier_at',
        'confirmed_by_supplier_at',
        'confirmed_by_koperasi_at',
        'received_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'po_date' => 'date',
        'estimated_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'estimated_total' => 'decimal:2',
        'confirmed_total' => 'decimal:2',
        'actual_total' => 'decimal:2',
        'sent_to_supplier_at' => 'datetime',
        'confirmed_by_supplier_at' => 'datetime',
        'confirmed_by_koperasi_at' => 'datetime',
        'received_at' => 'datetime',
        'status' => POStatusEnum::class,
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(POStatusHistory::class);
    }

    public function canTransitionTo(POStatusEnum $status): bool
    {
        return $this->status->canTransitionTo($status);
    }
}
