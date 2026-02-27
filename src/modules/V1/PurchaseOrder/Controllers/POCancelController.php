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
     *     path="/purchase-orders/{po}/cancel",
     *     summary="Cancel purchase order",
     *     description="Cancel a purchase order",
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
     *                 required={"reason"},
     *
     *                 @OA\Property(property="reason", type="string", example="No longer needed")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="PO cancelled successfully"
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
