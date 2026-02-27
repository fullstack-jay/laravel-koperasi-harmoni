<?php

declare(strict_types=1);

namespace Modules\V1\Kitchen\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\V1\Stock\Models\StockItem;
use Shared\Models\BaseModel;

class KitchenOrderItem extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'kitchen_order_id',
        'item_id',
        'requested_qty',
        'approved_qty',
        'unit_price',
        'subtotal',
        'buy_price',
        'profit',
        'notes',
        'stock_allocations',
    ];

    protected $casts = [
        'requested_qty' => 'integer',
        'approved_qty' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'buy_price' => 'decimal:2',
        'profit' => 'decimal:2',
        'stock_allocations' => 'array',
    ];

    public function kitchenOrder(): BelongsTo
    {
        return $this->belongsTo(KitchenOrder::class);
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class, 'item_id');
    }
}
