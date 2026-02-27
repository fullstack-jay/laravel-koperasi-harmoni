<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\V1\Stock\Models\StockItem;
use Shared\Models\BaseModel;

class PurchaseOrderItem extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'item_id',
        'estimated_unit_price',
        'estimated_qty',
        'estimated_subtotal',
        'actual_unit_price',
        'actual_qty',
        'actual_subtotal',
        'notes',
    ];

    protected $casts = [
        'estimated_unit_price' => 'decimal:2',
        'estimated_qty' => 'integer',
        'estimated_subtotal' => 'decimal:2',
        'actual_unit_price' => 'decimal:2',
        'actual_qty' => 'integer',
        'actual_subtotal' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class, 'item_id');
    }
}
