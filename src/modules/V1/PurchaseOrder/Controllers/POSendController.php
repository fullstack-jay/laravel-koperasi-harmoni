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
     *     description="Send purchase order to supplier and notify them",
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
     *     @OA\Response(
     *         response=200,
     *         description="PO sent successfully"
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Cannot send PO in current status"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function send(Request $request, PurchaseOrder $po)
    {
        try {
            // Send PO (update status to TERKIRIM)
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
