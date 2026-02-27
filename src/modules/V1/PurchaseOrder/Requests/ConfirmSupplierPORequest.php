<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ConfirmSupplierPORequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'uuid', 'exists:purchase_order_items,id'],
            'items.*.actual_unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.actual_qty' => ['required', 'integer', 'min:1'],
        ];
    }
}
