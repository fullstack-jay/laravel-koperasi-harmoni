<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="SupplierRejectRequest",
 *     title="Supplier Reject PO Request",
 *     description="Schema for supplier rejecting purchase order with detailed cancellation reasons",
 *     type="object",
 *     @OA\Property(property="cancellationReason", type="string", example="Mohon maaf, kami tidak dapat memenuhi pesanan..."),
 *     @OA\Property(property="cancelledItems", type="array", @OA\Items(
 *         type="object",
 *         required={"itemId", "reason", "stockType"},
 *         @OA\Property(property="itemId", type="string", format="uuid", description="Stock Item ID"),
 *         @OA\Property(property="reason", type="string", example="Stok tersisa 15 pack"),
 *         @OA\Property(property="stockType", type="string", enum={"remaining", "empty"}, example="remaining"),
 *         @OA\Property(property="quantity", type="integer", example=15, description="Available quantity when stockType is remaining"),
 *         @OA\Property(property="availableDate", type="string", example="2026-03-10 pukul 08:00", description="Available date when stockType is empty")
 *     ))
 * )
 */
class SupplierRejectRequest extends FormRequest
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
            'cancellationReason' => ['required', 'string'],
            'cancelledItems' => ['required', 'array', 'min:1'],
            'cancelledItems.*.itemId' => ['required', 'uuid', 'exists:stock_items,id'],
            'cancelledItems.*.reason' => ['required', 'string'],
            'cancelledItems.*.stockType' => ['required', 'in:remaining,empty'],
            'cancelledItems.*.quantity' => ['required', 'integer', 'min:0'],
            'cancelledItems.*.availableDate' => ['nullable', 'string'],
        ];
    }
}
