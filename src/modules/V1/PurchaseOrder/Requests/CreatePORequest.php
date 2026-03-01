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
            'poDate' => ['required', 'date'],
            'supplierId' => ['required', 'uuid', 'exists:suppliers,id'],
            'estimatedDeliveryDate' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.itemId' => ['required', 'uuid', 'exists:stock_items,id'],
            'items.*.estimatedUnitPrice' => ['required', 'numeric', 'min:0'],
            'items.*.estimatedQty' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Convert camelCase request data to snake_case for database operations
     */
    public function toSnakeCaseArray(): array
    {
        $data = $this->validated();

        return [
            'po_date' => $data['poDate'] ?? null,
            'supplier_id' => $data['supplierId'] ?? null,
            'estimated_delivery_date' => $data['estimatedDeliveryDate'] ?? null,
            'notes' => $data['notes'] ?? null,
            'items' => isset($data['items']) ? array_map(function ($item) {
                return [
                    'item_id' => $item['itemId'] ?? null,
                    'estimated_unit_price' => $item['estimatedUnitPrice'] ?? null,
                    'estimated_qty' => $item['estimatedQty'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ];
            }, $data['items']) : [],
            'created_by' => $this->user()?->id,
        ];
    }
}
