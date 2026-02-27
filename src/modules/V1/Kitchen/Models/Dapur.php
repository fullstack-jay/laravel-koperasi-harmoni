<?php

declare(strict_types=1);

namespace Modules\V1\Kitchen\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\V1\Kitchen\Models\KitchenOrder;
use Modules\V1\Kitchen\Models\SuratJalan;
use Shared\Models\BaseModel;

class Dapur extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'location',
        'pic_name',
        'pic_phone',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function kitchenOrders(): HasMany
    {
        return $this->hasMany(KitchenOrder::class);
    }

    public function suratJalans(): HasMany
    {
        return $this->hasMany(SuratJalan::class);
    }
}
