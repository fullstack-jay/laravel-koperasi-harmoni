<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Controllers;

use Exception;
use Illuminate\Http\Request;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;
use Modules\V1\PurchaseOrder\Resources\POResource;
use Modules\V1\PurchaseOrder\Service\POService;
use Shared\Helpers\ResponseHelper;

final class POCancelController extends POBaseController
{
    public function __construct(
        private POService $poService
    ) {}

    /**
     * @OA\Post(
     *     path="/PurchaseOrders/{po}/cancel",
     *     summary="Cancel purchase order",
     *     description="Cancel a DRAFT purchase order. Only POs with DRAFT status can be cancelled. The status will change to 'dibatalkan_draft'.",
     *     tags={"Purchase Orders"},
     *
     *     @OA\Parameter(
     *         name="po",
     *         in="path",
     *         required=true,
     *         description="Purchase Order UUID (must be in DRAFT status)",
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
     *                 required={"reason"},
     *
     *                 @OA\Property(property="reason", type="string", example="Budget tidak tersedia", description="Reason for cancellation")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="PO cancelled successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Purchase Order cancelled successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="poNumber", type="string"),
     *                 @OA\Property(property="status", type="string", example="dibatalkan_draft", description="Status changes to 'dibatalkan_draft' after cancellation"),
     *                 @OA\Property(property="statusLabel", type="string", example="Draft (Dibatalkan)", description="Label shows 'Draft (Dibatalkan)' for cancelled POs"),
     *                 @OA\Property(property="cancellationReason", type="string", example="Budget tidak tersedia")
     *             )
     *         )
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function __invoke(Request $request, PurchaseOrder $po)
    {
        try {
            $result = $this->poService->cancelPO(
                $po->id,
                $request->reason,
                $request->user()?->id
            );

            return ResponseHelper::success(
                data: new POResource($result),
                message: 'Purchase Order cancelled successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
