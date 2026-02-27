<?php

declare(strict_types=1);

namespace Modules\V1\Finance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\V1\Finance\Models\Transaction;

/**
 * @mixin Transaction
 */
class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date,
            'type' => $this->type->value,
            'type_label' => $this->type->getLabel(),
            'category' => $this->category->value,
            'category_label' => $this->category->getLabel(),
            'amount' => (float) $this->amount,
            'profit' => $this->profit ? (float) $this->profit : null,
            'margin' => $this->margin ? (float) $this->margin : null,
            'reference' => $this->reference,
            'reference_id' => $this->reference_id,
            'payment_status' => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->getLabel(),
            'payment_date' => $this->payment_date,
            'notes' => $this->notes,
            'items' => $this->items,
            'created_at' => $this->created_at,
        ];
    }
}
