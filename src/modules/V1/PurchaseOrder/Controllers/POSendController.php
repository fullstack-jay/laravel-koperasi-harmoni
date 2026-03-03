<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Controllers;

use Exception;
use Illuminate\Http\Request;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;
use Modules\V1\PurchaseOrder\Notifications\PurchaseOrderSentNotification;
use Modules\V1\PurchaseOrder\Resources\POResource;
use Modules\V1\PurchaseOrder\Service\POService;
use Modules\V1\User\Models\User;
use Shared\Helpers\ResponseHelper;

final class POSendController extends POBaseController
{
    public function __construct(
        private POService $poService
    ) {}

    /**
     * @OA\Post(
     *     path="/PurchaseOrders/{po}/Send",
     *     summary="Send PO to supplier",
     *     description="Send purchase order to supplier and notify them. Only DRAFT and DIBATALKAN_DRAFT status POs can be sent.",
     *     tags={"Purchase Orders"},
     *
     *     @OA\Parameter(
     *         name="po",
     *         in="path",
     *         required=true,
     *         description="Purchase Order UUID (must be in DRAFT or DIBATALKAN_DRAFT status)",
     *
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="PO sent successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Purchase Order berhasil dikirim ke 2 supplier user(s)"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="poNumber", type="string"),
     *                 @OA\Property(property="status", type="string", example="menunggu_persetujuan_supplier"),
     *                 @OA\Property(property="statusLabel", type="string", example="Menunggu Persetujuan Supplier")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Cannot send PO - Invalid status",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Purchase Order tidak dapat dikirim. Status saat ini: Selesai. Hanya PO dengan status Draft atau Draft (Dibatalkan) yang dapat dikirim.")
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function send(Request $request, PurchaseOrder $po)
    {
        try {
            // Send PO (update status to MENUNGGU_PERSETUJUAN_SUPPLIER)
            $updatedPO = $this->poService->sendToSupplier(
                $po->id,
                $request->user()?->id
            );

            // Get supplier users to notify
            $supplierUsers = User::where('supplier_id', $updatedPO->supplier_id)
                ->whereNotNull('email')
                ->get();

            // Send notification to all supplier users
            foreach ($supplierUsers as $user) {
                $user->notify(new PurchaseOrderSentNotification($updatedPO));
            }

            return ResponseHelper::success(
                data: new POResource($updatedPO->load('items')),
                message: "Purchase Order berhasil dikirim ke {$supplierUsers->count()} supplier user(s)"
            );
        } catch (Exception $e) {
            return ResponseHelper::error(
                'Gagal mengirim Purchase Order: ' . $e->getMessage()
            );
        }
    }
}
