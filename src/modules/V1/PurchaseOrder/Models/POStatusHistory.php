<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Shared\Models\BaseModel;

class POStatusHistory extends BaseModel
{
    use HasFactory;

    protected $table = 'po_status_histories';

    protected $fillable = [
        'purchase_order_id',
        'from_status',
        'to_status',
        'notes',
        'changed_by',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
