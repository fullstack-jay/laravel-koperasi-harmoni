<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreatePORequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'po_date' => ['required', 'date'],
            'supplier_id' => ['required', 'uuid', 'exists:suppliers,id'],
            'estimated_delivery_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'uuid', 'exists:stock_items,id'],
            'items.*.estimated_unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.estimated_qty' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
