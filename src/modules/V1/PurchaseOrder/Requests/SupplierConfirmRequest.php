<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="SupplierConfirmRequest",
 *     title="Supplier Confirm PO Request",
 *     description="Schema for supplier confirming purchase order with prices and expiry batch information. Note: Items with category KMN or KEMASAN are exempt from expiry validation.",
 *     type="object",
 *     required={"items"},
 *     @OA\Property(property="invoiceNumber", type="string", example="INV-2026-001", description="Invoice number from supplier"),
 *     @OA\Property(property="items", type="array", @OA\Items(
 *         type="object",
 *         required={"itemId", "actualPrice"},
 *         @OA\Property(property="itemId", type="string", format="uuid", description="Stock Item ID", example="a1321f01-a6a7-4a19-9013-d82b80cb2ffc"),
 *         @OA\Property(property="actualPrice", type="number", format="float", example=15000, description="Actual price from supplier (will update buy_price in supplier_items master if different from estimated price)"),
 *         @OA\Property(property="isSameExpiry", type="boolean", description="Whether all stock has same expiry date. Optional and only required for NON-KMN/KEMASAN items", example=true),
 *         @OA\Property(property="expiryDate", type="string", format="date", description="Expiry date if isSameExpiry=true", example="2026-03-15", nullable=true),
 *         @OA\Property(property="expiredBatches", type="array", @OA\Items(
 *             type="object",
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
            'items.*.isSameExpiry' => ['nullable', 'boolean'],
            'items.*.expiryDate' => ['nullable', 'date'],
            'items.*.expiredBatches' => ['nullable', 'array'],
            'items.*.expiredBatches.*.batchNumber' => ['nullable', 'integer', 'min:1'],
            'items.*.expiredBatches.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.expiredBatches.*.expiryDate' => ['nullable', 'date'],
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
            'items.*.itemId.required' => 'ID barang wajib diisi',
            'items.*.itemId.uuid' => 'Format ID barang tidak valid (harus UUID)',
            'items.*.itemId.exists' => 'Barang tidak ditemukan',
            'items.*.actualPrice.required' => 'Harga actual wajib diisi',
            'items.*.actualPrice.numeric' => 'Harga actual harus berupa angka',
            'items.*.actualPrice.min' => 'Harga actual tidak boleh negatif',
            'items.*.isSameExpiry.required' => 'Informasi tanggal kadaluarsa wajib diisi',
            'items.*.expiryDate.required_if' => 'Tanggal kadaluarsa wajib diisi when semua barang memiliki tanggal kadaluarsa yang sama',
            'items.*.expiryDate.date' => 'Format tanggal kadaluarsa tidak valid',
            'items.*.expiredBatches.required_if' => 'Informasi batch kadaluarsa wajib diisi ketika barang memiliki tanggal kadaluarsa yang berbeda',
            'items.*.expiredBatches.array' => 'Format batch kadaluarsa tidak valid',
            'items.*.expiredBatches.min' => 'Minimal satu batch kadaluarsa wajib diisi',
            'items.*.expiredBatches.*.batchNumber.required' => 'Nomor batch wajib diisi',
            'items.*.expiredBatches.*.batchNumber.integer' => 'Nomor batch harus berupa angka',
            'items.*.expiredBatches.*.batchNumber.min' => 'Nomor batch minimal 1',
            'items.*.expiredBatches.*.quantity.required' => 'Jumlah batch wajib diisi',
            'items.*.expiredBatches.*.quantity.integer' => 'Jumlah batch harus berupa angka',
            'items.*.expiredBatches.*.quantity.min' => 'Jumlah batch minimal 1',
            'items.*.expiredBatches.*.expiryDate.required' => 'Tanggal kadaluarsa batch wajib diisi',
            'items.*.expiredBatches.*.expiryDate.date' => 'Format tanggal kadaluarsa batch tidak valid',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);

            foreach ($items as $index => $item) {
                $itemKey = "items.{$index}";
                $isSameExpiry = $item['isSameExpiry'] ?? false;
                $expiryDate = $item['expiryDate'] ?? null;
                $expiredBatches = $item['expiredBatches'] ?? null;

                // Get stock item to check category
                $stockItem = \Modules\V1\Stock\Models\StockItem::find($item['itemId']);
                $category = $stockItem->category ?? null;

                // Skip expiry validation for KMN and KEMASAN categories
                $isExpiryExempt = in_array($category, ['KMN', 'KEMASAN']);

                if ($isExpiryExempt) {
                    // For KMN/KEMASAN, expiry fields are optional, skip all validations
                    continue;
                }

                // If isSameExpiry is true, expiryDate must be provided
                if ($isSameExpiry === true) {
                    if (empty($expiryDate)) {
                        $validator->errors()->add("{$itemKey}.expiryDate", 'Tanggal kadaluarsa wajib diisi karena semua barang memiliki tanggal kadaluarsa yang sama.');
                    }
                }
                // If isSameExpiry is false, expiredBatches must be provided with at least 1 batch
                else {
                    if (empty($expiredBatches) || !is_array($expiredBatches) || count($expiredBatches) === 0) {
                        $validator->errors()->add("{$itemKey}.expiredBatches", 'Informasi batch kadaluarsa wajib diisi dengan minimal satu batch karena barang memiliki tanggal kadaluarsa yang berbeda.');
                    }

                    // Validate batch quantities sum must equal estimated_qty from PO
                    if (!empty($expiredBatches) && is_array($expiredBatches)) {
                        // Get the PO item to get estimated_qty
                        $purchaseOrder = $this->route('po');
                        if ($purchaseOrder) {
                            $poItem = \Modules\V1\PurchaseOrder\Models\PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)
                                ->where('item_id', $item['itemId'])
                                ->first();

                            if ($poItem) {
                                $estimatedQty = $poItem->estimated_qty;
                                $totalBatchQty = 0;

                                foreach ($expiredBatches as $batchIndex => $batch) {
                                    $batchQty = $batch['quantity'] ?? 0;
                                    $totalBatchQty += $batchQty;
                                }

                                // Total batch qty must exactly equal estimated qty
                                if ($totalBatchQty !== $estimatedQty) {
                                    if ($totalBatchQty > $estimatedQty) {
                                        $validator->errors()->add("{$itemKey}.expiredBatches",
                                            "Total jumlah quantity dalam batch (" . number_format($totalBatchQty, 0, ',', '.') . ") " .
                                            "melebihi jumlah quantity yang dipesan (" . number_format($estimatedQty, 0, ',', '.') . "). " .
                                            "Mohon sesuaikan jumlah quantity dalam setiap batch."
                                        );
                                    } else {
                                        $validator->errors()->add("{$itemKey}.expiredBatches",
                                            "Total jumlah quantity dalam batch (" . number_format($totalBatchQty, 0, ',', '.') . ") " .
                                            "kurang dari jumlah quantity yang dipesan (" . number_format($estimatedQty, 0, ',', '.') . "). " .
                                            "Mohon sesuaikan jumlah quantity dalam setiap batch agar totalnya sesuai."
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }
        });
    }
}
