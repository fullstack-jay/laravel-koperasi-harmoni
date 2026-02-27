<?php

declare(strict_types=1);

namespace Modules\V1\Kitchen\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Shared\Models\BaseModel;

class SuratJalan extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'sj_number',
        'kitchen_order_id',
        'dapur_id',
        'sj_date',
        'driver_name',
        'vehicle_plate',
        'notes',
        'delivered_at',
        'receiver_name',
        'receiver_notes',
        'created_by',
    ];

    protected $casts = [
        'sj_date' => 'date',
        'delivered_at' => 'datetime',
    ];

    public function kitchenOrder(): BelongsTo
    {
        return $this->belongsTo(KitchenOrder::class);
    }

    public function dapur(): BelongsTo
    {
        return $this->belongsTo(Dapur::class);
    }
}
