<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePORequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'po_date' => ['nullable', 'date'],
            'supplier_id' => ['nullable', 'uuid', 'exists:suppliers,id'],
            'estimated_delivery_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'uuid', 'exists:stock_items,id'],
            'items.*.estimated_unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.estimated_qty' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
