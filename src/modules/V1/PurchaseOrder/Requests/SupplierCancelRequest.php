<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="SupplierCancelRequest",
 *     title="Supplier Cancel PO Request",
 *     description="Schema for supplier cancelling purchase order with detailed cancellation information and expired tracking",
 *     type="object",
 *     required={"cancelItems"},
 *     @OA\Property(property="poId", type="string", format="uuid", description="Purchase Order ID (optional, taken from route parameter)", example="a1339d14-9065-418b-a605-148b33d14a09"),
 *     @OA\Property(property="cancelItems", type="array", @OA\Items(
 *         type="object",
 *         required={"itemId", "itemName", "estimatedQty", "unit", "reason", "quantity"},
 *         @OA\Property(property="itemId", type="string", format="uuid", description="Stock Item ID", example="item-1"),
 *         @OA\Property(property="itemName", type="string", description="Item name", example="Bayam Ikat"),
 *         @OA\Property(property="estimatedQty", type="integer", description="Estimated quantity in PO", example=10),
 *         @OA\Property(property="unit", type="string", description="Unit of measurement", example="pack"),
 *         @OA\Property(property="reason", type="string", description="Cancellation reason code (STOK_TERSISA, STOK_HABIS)", example="STOK_TERSISA", enum={"STOK_TERSISA", "STOK_HABIS"}),
 *         @OA\Property(property="quantity", type="integer", description="Current available quantity", example=5),
 *         @OA\Property(property="stokBertambah", type="integer", description="Scheduled quantity that will be added to current_stock in the future", example=60, nullable=true),
 *         @OA\Property(property="isSameExpired", type="boolean", description="Whether all stock has same expiry date", example=true),
 *         @OA\Property(property="tanggalExpired", type="string", format="date", description="Expiry date if isSameExpired=true", example="2026-06-15"),
 *         @OA\Property(property="quantityExpiredTerdekat", type="integer", description="Quantity for nearest expiry date", example=30),
 *         @OA\Property(property="tanggalExpiredTerdekat", type="string", format="date", description="Nearest expiry date", example="2026-06-15"),
 *         @OA\Property(property="quantityExpiredTerjauh", type="integer", description="Quantity for furthest expiry date", example=20),
 *         @OA\Property(property="tanggalExpiredTerjauh", type="string", format="date", description="Furthest expiry date", example="2026-08-20"),
 *         @OA\Property(property="availableDate", type="string", format="date-time", description="When the scheduled stock will be available (will populate scheduled_at field)", example="2026-03-04T22:58:00Z", nullable=true)
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
            'cancelItems.*.stokBertambah' => ['nullable', 'integer', 'min:0'],
            'cancelItems.*.isSameExpired' => ['required', 'boolean'],
            'cancelItems.*.tanggalExpired' => ['required_if:cancelItems.*.isSameExpired,true', 'nullable', 'date'],
            'cancelItems.*.quantityExpiredTerdekat' => ['required_if:cancelItems.*.isSameExpired,false', 'nullable', 'integer', 'min:0'],
            'cancelItems.*.tanggalExpiredTerdekat' => ['required_if:cancelItems.*.isSameExpired,false', 'nullable', 'date'],
            'cancelItems.*.quantityExpiredTerjauh' => ['required_if:cancelItems.*.isSameExpired,false', 'nullable', 'integer', 'min:0'],
            'cancelItems.*.tanggalExpiredTerjauh' => ['required_if:cancelItems.*.isSameExpired,false', 'nullable', 'date'],
            'cancelItems.*.availableDate' => ['nullable', 'date'],
            'message' => ['nullable', 'string'],
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
            'cancelItems.*.itemId.required' => 'ID barang wajib diisi',
            'cancelItems.*.itemId.uuid' => 'Format ID barang tidak valid (harus UUID)',
            'cancelItems.*.itemId.exists' => 'Barang tidak ditemukan',
            'cancelItems.*.itemName.required' => 'Nama barang wajib diisi',
            'cancelItems.*.estimatedQty.required' => 'Perkiraan jumlah wajib diisi',
            'cancelItems.*.estimatedQty.integer' => 'Perkiraan jumlah harus berupa angka',
            'cancelItems.*.estimatedQty.min' => 'Perkiraan jumlah minimal 1',
            'cancelItems.*.unit.required' => 'Satuan barang wajib diisi',
            'cancelItems.*.reason.required' => 'Alasan pembatalan wajib diisi',
            'cancelItems.*.reason.in' => 'Alasan pembatalan harus salah satu dari: STOK_TERSISA, STOK_HABIS',
            'cancelItems.*.quantity.required' => 'Jumlah stok tersedia wajib diisi',
            'cancelItems.*.quantity.integer' => 'Jumlah stok harus berupa angka',
            'cancelItems.*.quantity.min' => 'Jumlah stok tidak boleh negatif',
            'cancelItems.*.stokBertambah.integer' => 'Jumlah stok tambahan harus berupa angka',
            'cancelItems.*.stokBertambah.min' => 'Jumlah stok tambahan tidak boleh negatif',
            'cancelItems.*.isSameExpired.required' => 'Informasi tanggal kadaluarsa wajib diisi',
            'cancelItems.*.tanggalExpired.required_if' => 'Tanggal kadaluarsa wajib diisi jika semua barang memiliki tanggal kadaluarsa yang sama',
            'cancelItems.*.tanggalExpired.date' => 'Format tanggal kadaluarsa tidak valid',
            'cancelItems.*.quantityExpiredTerdekat.required_if' => 'Jumlah batch kadaluarsa terdekat wajib diisi',
            'cancelItems.*.quantityExpiredTerdekat.integer' => 'Jumlah batch kadaluarsa terdekat harus berupa angka',
            'cancelItems.*.quantityExpiredTerdekat.min' => 'Jumlah batch kadaluarsa terdekat tidak boleh negatif',
            'cancelItems.*.tanggalExpiredTerdekat.required_if' => 'Tanggal kadaluarsa terdekat wajib diisi',
            'cancelItems.*.tanggalExpiredTerdekat.date' => 'Format tanggal kadaluarsa terdekat tidak valid',
            'cancelItems.*.quantityExpiredTerjauh.required_if' => 'Jumlah batch kadaluarsa terjauh wajib diisi',
            'cancelItems.*.quantityExpiredTerjauh.integer' => 'Jumlah batch kadaluarsa terjauh harus berupa angka',
            'cancelItems.*.quantityExpiredTerjauh.min' => 'Jumlah batch kadaluarsa terjauh tidak boleh negatif',
            'cancelItems.*.tanggalExpiredTerjauh.required_if' => 'Tanggal kadaluarsa terjauh wajib diisi',
            'cancelItems.*.tanggalExpiredTerjauh.date' => 'Format tanggal kadaluarsa terjauh tidak valid',
            'cancelItems.*.availableDate.date' => 'Format tanggal stok tersedia tidak valid',
        ];
    }
}
