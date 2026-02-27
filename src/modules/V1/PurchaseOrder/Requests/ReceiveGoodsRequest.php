<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReceiveGoodsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'received_date' => ['required', 'date'],
            'invoice_number' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'uuid', 'exists:purchase_order_items,id'],
            'items.*.received_qty' => ['required', 'integer', 'min:1'],
            'items.*.expiry_date' => ['nullable', 'date'],
            'items.*.production_date' => ['nullable', 'date'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
