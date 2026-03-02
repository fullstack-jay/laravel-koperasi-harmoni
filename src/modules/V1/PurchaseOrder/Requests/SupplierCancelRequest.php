<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="SupplierCancelRequest",
 *     title="Supplier Cancel PO Request",
 *     description="Schema for supplier cancelling purchase order with detailed cancellation information",
 *     type="object",
 *     required={"cancelItems"},
 *     @OA\Property(property="poId", type="string", format="uuid", description="Purchase Order ID (optional, taken from route parameter)", example="a1339d14-9065-418b-a605-148b33d14a09"),
 *     @OA\Property(property="cancelItems", type="array", @OA\Items(
 *         type="object",
 *         required={"itemId", "itemName", "estimatedQty", "unit", "reason"},
 *         @OA\Property(property="itemId", type="string", format="uuid", description="Stock Item ID", example="item-1"),
 *         @OA\Property(property="itemName", type="string", description="Item name", example="Bayam Ikat"),
 *         @OA\Property(property="estimatedQty", type="integer", description="Estimated quantity in PO", example=10),
 *         @OA\Property(property="unit", type="string", description="Unit of measurement", example="pack"),
 *         @OA\Property(property="reason", type="string", description="Cancellation reason code (STOK_TERSISA, STOK_HABIS)", example="STOK_TERSISA", enum={"STOK_TERSISA", "STOK_HABIS"}),
 *         @OA\Property(property="quantity", type="integer", description="Available quantity (required for STOK_TERSISA, optional for STOK_HABIS)", example=5)
 *     )),
 *     @OA\Property(property="message", type="string", description="Detailed cancellation message", example="Mohon maaf, untuk PO-001 berikut item yang tidak dapat dipenuhi...")
 * )
 */
class SupplierCancelRequest extends FormRequest
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
            'poId' => ['nullable', 'uuid', 'exists:purchase_orders,id'], // Optional, PO is taken from route parameter
            'cancelItems' => ['required', 'array', 'min:1'],
            'cancelItems.*.itemId' => ['required', 'uuid', 'exists:stock_items,id'],
            'cancelItems.*.itemName' => ['required', 'string'],
            'cancelItems.*.estimatedQty' => ['required', 'integer', 'min:1'],
            'cancelItems.*.unit' => ['required', 'string'],
            'cancelItems.*.reason' => ['required', 'string', 'in:STOK_TERSISA,STOK_HABIS'],
            'cancelItems.*.quantity' => ['required', 'integer', 'min:0'],
            'message' => ['nullable', 'string'],
        ];
    }
}
