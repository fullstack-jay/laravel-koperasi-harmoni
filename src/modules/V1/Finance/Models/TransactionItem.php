<?php

declare(strict_types=1);

namespace Modules\V1\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Shared\Models\BaseModel;

class TransactionItem extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'item_id',
        'qty',
        'buy_price',
        'sell_price',
        'subtotal',
        'profit',
        'margin',
        'batch_details',
    ];

    protected $casts = [
        'qty' => 'integer',
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'profit' => 'decimal:2',
        'margin' => 'decimal:2',
        'batch_details' => 'array',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
