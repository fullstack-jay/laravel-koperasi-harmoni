<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Controllers;

use Exception;
use Illuminate\Http\Request;
use Modules\V1\PurchaseOrder\Requests\CreatePORequest;
use Modules\V1\PurchaseOrder\Resources\POResource;
use Modules\V1\PurchaseOrder\Service\POService;
use Shared\Helpers\ResponseHelper;

final class POCreateController extends POBaseController
{
    public function __construct(
        private POService $poService
    ) {}

    /**
     * @OA\Post(
     *     path="/PurchaseOrders/Create",
     *     summary="Create purchase order",
     *     description="Create a new draft purchase order",
     *     tags={"Purchase Orders"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 required={"supplierId", "items"},
     *
     *                 @OA\Property(property="supplierId", type="string", format="uuid"),
     *                 @OA\Property(property="poDate", type="string", format="date", example="2025-01-15"),
     *                 @OA\Property(property="estimatedDeliveryDate", type="string", format="date", example="2025-01-20"),
     *                 @OA\Property(property="items", type="array", @OA\Items(
     *                     type="object",
     *                     required={"itemId", "estimatedQty", "estimatedUnitPrice"},
     *                     @OA\Property(property="itemId", type="string", format="uuid"),
     *                     @OA\Property(property="estimatedQty", type="integer", example=100),
     *                     @OA\Property(property="estimatedUnitPrice", type="number", format="float", example=15000),
     *                     @OA\Property(property="notes", type="string", nullable=true)
     *                 )),
     *                 @OA\Property(property="notes", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="PO created successfully"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function __invoke(CreatePORequest $request)
    {
        try {
            $po = $this->poService->createDraftPO($request->toSnakeCaseArray());

            return ResponseHelper::success(
                data: new POResource($po),
                message: 'Purchase Order created successfully',
                status: 201
            );
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
