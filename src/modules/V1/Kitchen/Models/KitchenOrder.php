<?php

declare(strict_types=1);

namespace Modules\V1\Kitchen\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\V1\Kitchen\Enums\OrderStatusEnum;
use Shared\Models\BaseModel;

class KitchenOrder extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'order_date',
        'dapur_id',
        'status',
        'estimated_total',
        'actual_total',
        'notes',
        'rejection_reason',
        'sent_at',
        'processed_at',
        'delivered_at',
        'received_at',
        'qr_code',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'estimated_total' => 'decimal:2',
        'actual_total' => 'decimal:2',
        'sent_at' => 'datetime',
        'processed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'received_at' => 'datetime',
        'status' => OrderStatusEnum::class,
    ];

    protected $with = ['items'];

    public function dapur(): BelongsTo
    {
        return $this->belongsTo(Dapur::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(KitchenOrderItem::class);
    }

    public function suratJalan(): HasMany
    {
        return $this->hasMany(SuratJalan::class);
    }

    public function canTransitionTo(OrderStatusEnum $status): bool
    {
        return $this->status->canTransitionTo($status);
    }
}
