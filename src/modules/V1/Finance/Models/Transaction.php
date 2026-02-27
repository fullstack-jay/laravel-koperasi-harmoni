<?php

declare(strict_types=1);

namespace Modules\V1\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\V1\Finance\Enums\PaymentStatusEnum;
use Modules\V1\Finance\Enums\TransactionCategoryEnum;
use Modules\V1\Finance\Enums\TransactionTypeEnum;
use Shared\Models\BaseModel;

class Transaction extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'date',
        'type',
        'category',
        'amount',
        'profit',
        'margin',
        'reference',
        'reference_id',
        'supplier_id',
        'dapur_id',
        'items',
        'payment_status',
        'payment_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'profit' => 'decimal:2',
        'margin' => 'decimal:2',
        'items' => 'array',
        'type' => TransactionTypeEnum::class,
        'category' => TransactionCategoryEnum::class,
        'payment_status' => PaymentStatusEnum::class,
    ];

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }
}
