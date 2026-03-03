<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="SupplierConfirmRequest",
 *     title="Supplier Confirm PO Request",
 *     description="Schema for supplier confirming purchase order with prices and expiry batch information",
 *     type="object",
 *     required={"items"},
 *     @OA\Property(property="invoiceNumber", type="string", example="INV-2026-001", description="Invoice number from supplier"),
 *     @OA\Property(property="items", type="array", @OA\Items(
 *         type="object",
 *         required={"itemId", "actualPrice", "isSameExpiry"},
 *         @OA\Property(property="itemId", type="string", format="uuid", description="Stock Item ID", example="a1321f01-a6a7-4a19-9013-d82b80cb2ffc"),
 *         @OA\Property(property="actualPrice", type="number", format="float", example=15000, description="Actual price from supplier (will update buy_price in supplier_items master if different from estimated price)"),
 *         @OA\Property(property="isSameExpiry", type="boolean", description="Whether all stock has same expiry date", example=true),
 *         @OA\Property(property="expiryDate", type="string", format="date", description="Expiry date if isSameExpiry=true", example="2026-03-15", nullable=true),
 *         @OA\Property(property="expiredBatches", type="array", @OA\Items(
 *             type="object",
 *             required={"batchNumber", "quantity", "expiryDate"},
 *             @OA\Property(property="batchNumber", type="integer", description="Batch number", example=1),
 *             @OA\Property(property="quantity", type="integer", description="Quantity in this batch", example=10),
 *             @OA\Property(property="expiryDate", type="string", format="date", description="Expiry date for this batch", example="2026-03-02")
 *         ))
 *     ))
 * )
 */
class SupplierConfirmRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'invoiceNumber' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.itemId' => ['required', 'uuid', 'exists:stock_items,id'],
            'items.*.actualPrice' => ['required', 'numeric', 'min:0'],
            'items.*.isSameExpiry' => ['required', 'boolean'],
            'items.*.expiryDate' => ['required_if:items.*.isSameExpiry,true', 'nullable', 'date'],
            'items.*.expiredBatches' => ['required_if:items.*.isSameExpiry,false', 'nullable', 'array', 'min:1'],
            'items.*.expiredBatches.*.batchNumber' => ['required', 'integer', 'min:1'],
            'items.*.expiredBatches.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.expiredBatches.*.expiryDate' => ['required', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.*.expiryDate.required_if' => 'The expiryDate field is required when isSameExpiry is true',
            'items.*.expiredBatches.required_if' => 'The expiredBatches field is required when isSameExpiry is false',
            'items.*.expiredBatches.min' => 'At least one batch is required when isSameExpiry is false',
        ];
    }
}
