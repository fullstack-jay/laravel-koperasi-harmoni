<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Controllers;

use Exception;
use Illuminate\Http\Request;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;
use Modules\V1\PurchaseOrder\Requests\SupplierCancelRequest;
use Modules\V1\PurchaseOrder\Requests\SupplierConfirmRequest;
use Modules\V1\PurchaseOrder\Requests\SupplierRejectRequest;
use Modules\V1\PurchaseOrder\Resources\POResource;
use Modules\V1\PurchaseOrder\Service\POSupplierService;
use Shared\Helpers\ResponseHelper;

final class POSupplierController extends POBaseController
{
    public function __construct(
        private POSupplierService $supplierService
    ) {}

    /**
     * @OA\Post(
     *     path="/PurchaseOrders/{po}/Supplier/Confirm",
     *     summary="Supplier confirms PO with prices and expiry batch information",
     *     description="Supplier confirms purchase order by providing actual prices, invoice number, and expiry batch information. If prices changed, the system will automatically update the buy_price in supplier_items master table and set price_updated_at to current timestamp. Expiry information will be saved to stock_items table. PO status will change to PERUBAHAN_HARGA if there are price changes, or DIKONFIRMASI_SUPPLIER if no changes.",
     *     tags={"Purchase Orders"},
     *
     *     @OA\Parameter(
     *         name="po",
     *         in="path",
     *         required=true,
     *         description="Purchase Order UUID",
     *
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 required={"items"},
     *
     *                 @OA\Property(property="invoiceNumber", type="string", example="INV-2026-001", description="Invoice number from supplier"),
     *                 @OA\Property(property="items", type="array", @OA\Items(
     *                     type="object",
     *                     required={"itemId", "actualPrice", "isSameExpiry"},
     *                     @OA\Property(property="itemId", type="string", format="uuid", description="Stock Item ID", example="a1321f01-a6a7-4a19-9013-d82b80cb2ffc"),
     *                     @OA\Property(property="actualPrice", type="number", format="float", example=15000, description="Actual price from supplier (will update buy_price in supplier_items master if different)"),
     *                     @OA\Property(property="isSameExpiry", type="boolean", description="Whether all stock has same expiry date", example=true),
     *                     @OA\Property(property="expiryDate", type="string", format="date", description="Expiry date if isSameExpiry=true", example="2026-03-15", nullable=true),
     *                     @OA\Property(property="expiredBatches", type="array", @OA\Items(
     *                         type="object",
     *                         required={"batchNumber", "quantity", "expiryDate"},
     *                         @OA\Property(property="batchNumber", type="integer", description="Batch number (1 = nearest expiry)", example=1),
     *                         @OA\Property(property="quantity", type="integer", description="Quantity in this batch", example=10),
     *                         @OA\Property(property="expiryDate", type="string", format="date", description="Expiry date for this batch", example="2026-03-02")
     *                     ))
     *                 ))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="PO confirmed successfully. If prices changed: status=PERUBAHAN_HARGA, buy_price updated in supplier_items, price_updated_at set. If no changes: status=DIKONFIRMASI_SUPPLIER. Expiry info saved to stock_items."
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function confirm(SupplierConfirmRequest $request, PurchaseOrder $po)
    {
        try {
            $request->merge(['user_id' => $request->user()?->id]);

            $result = $this->supplierService->confirmPO(
                $po->id,
                $request->items,
                $request->invoiceNumber,
                $request->user_id
            );

            return ResponseHelper::success(
                data: new POResource($result),
                message: 'Purchase Order confirmed successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/PurchaseOrders/{po}/Supplier/Reject",
     *     summary="Supplier rejects PO with detailed cancellation reasons",
     *     description="Supplier rejects purchase order with detailed reasons and item-level cancellation information",
     *     tags={"Purchase Orders"},
     *
     *     @OA\Parameter(
     *         name="po",
     *         in="path",
     *         required=true,
     *         description="Purchase Order UUID",
     *
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 required={"cancellationReason", "cancelledItems"},
     *
     *                 @OA\Property(property="cancellationReason", type="string", example="Mohon maaf, kami tidak dapat memenuhi pesanan untuk item berikut:\n\n• Bayam Ikat (qty: 20 pack): Stok tersisa 15 pack\n• Telur Kampung (qty: 30 pcs): Stok habis, akan kembali tersedia pada 2026-03-10 pukul 08:00\n\nMohon dikonfirmasi dan diproses perubahannya. Terima kasih."),
     *                 @OA\Property(property="cancelledItems", type="array", @OA\Items(
     *                     type="object",
     *                     required={"itemId", "reason", "stockType"},
     *                     @OA\Property(property="itemId", type="string", format="uuid", example="a1321f01-a6a7-4a19-9013-d82b80cb2ffc"),
     *                     @OA\Property(property="reason", type="string", example="Stok tersisa 15 pack"),
     *                     @OA\Property(property="stockType", type="string", enum={"remaining", "empty"}, example="remaining"),
     *                     @OA\Property(property="quantity", type="integer", example=15, description="Available quantity when stockType is remaining"),
     *                     @OA\Property(property="availableDate", type="string", example="2026-03-10 pukul 08:00", description="Available date when stockType is empty")
     *                 ))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="PO rejected successfully",
     *
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Purchase Order rejected successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="poNumber", type="string"),
     *                 @OA\Property(property="status", type="string", example="dibatalkan"),
     *                 @OA\Property(property="cancellationReason", type="string"),
     *                 @OA\Property(property="cancelledItems", type="array", @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="itemId", type="string", format="uuid"),
     *                     @OA\Property(property="reason", type="string"),
     *                     @OA\Property(property="stockType", type="string"),
     *                     @OA\Property(property="quantity", type="integer"),
     *                     @OA\Property(property="availableDate", type="string")
     *                 ))
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function reject(SupplierRejectRequest $request, PurchaseOrder $po)
    {
        try {
            $result = $this->supplierService->rejectPO(
                $po->id,
                $request->cancellationReason,
                $request->cancelledItems,
                $request->user()?->id
            );

            return ResponseHelper::success(
                data: new POResource($result),
                message: 'Purchase Order rejected successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/PurchaseOrders/{po}/Supplier/Cancel",
     *     summary="Supplier cancels PO",
     *     description="Supplier cancels purchase order due to stock issues or other reasons",
     *     tags={"Purchase Orders"},
     *
     *     @OA\Parameter(
     *         name="po",
     *         in="path",
     *         required=true,
     *         description="Purchase Order UUID",
     *
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 required={"poId", "cancelItems"},
     *
     *                 @OA\Property(property="poId", type="string", format="uuid", example="a1339d14-9065-418b-a605-148b33d14a09"),
     *                 @OA\Property(property="cancelItems", type="array", @OA\Items(
     *                     type="object",
     *                     required={"itemId", "itemName", "estimatedQty", "unit", "reason", "quantity"},
     *                     @OA\Property(property="itemId", type="string", format="uuid", example="item-1"),
     *                     @OA\Property(property="itemName", type="string", example="Bayam Ikat"),
     *                     @OA\Property(property="estimatedQty", type="integer", example=10),
     *                     @OA\Property(property="unit", type="string", example="pack"),
     *                     @OA\Property(property="reason", type="string", example="STOK_TERSISA"),
     *                     @OA\Property(property="quantity", type="integer", example=5)
     *                 )),
     *                 @OA\Property(property="message", type="string", example="Mohon maaf, untuk PO-001 berikut item yang tidak dapat dipenuhi...")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="PO cancelled successfully",
     *
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="PO berhasil dibatalkan"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="poNumber", type="string", example="PO-001"),
     *                 @OA\Property(property="status", type="string", example="DIBATALKAN"),
     *                 @OA\Property(property="isCancelled", type="boolean", example=true),
     *                 @OA\Property(property="cancelledAt", type="string", format="date-time", example="2026-03-02T10:30:00"),
     *                 @OA\Property(property="cancelReason", type="string", example="Supplier membatalkan sebagian item"),
     *                 @OA\Property(property="cancelledItems", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function cancel(SupplierCancelRequest $request, PurchaseOrder $po)
    {
        try {
            // Use PO from route parameter instead of request body
            $result = $this->supplierService->cancelPO(
                $po->id,
                $request->cancelItems,
                $request->message,
                $request->user()?->id
            );

            return ResponseHelper::success(
                data: new POResource($result),
                message: 'PO berhasil dibatalkan'
            );
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
