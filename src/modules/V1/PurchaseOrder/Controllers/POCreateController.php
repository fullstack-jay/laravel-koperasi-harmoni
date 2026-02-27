<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Controllers;

use Exception;
use Illuminate\Http\Request;
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
     *     path="/purchase-orders/create",
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
     *                 required={"supplier_id", "items"},
     *
     *                 @OA\Property(property="supplier_id", type="string", format="uuid"),
     *                 @OA\Property(property="po_date", type="string", format="date", example="2025-01-15"),
     *                 @OA\Property(property="estimated_delivery_date", type="string", format="date", example="2025-01-20"),
     *                 @OA\Property(property="items", type="array", @OA\Items(
     *                     type="object",
     *                     required={"item_id", "estimated_qty", "estimated_unit_price"},
     *                     @OA\Property(property="item_id", type="string", format="uuid"),
     *                     @OA\Property(property="estimated_qty", type="integer", example=100),
     *                     @OA\Property(property="estimated_unit_price", type="number", format="float", example=15000)
     *                 )),
     *                 @OA\Property(property="notes", type="string")
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
    public function __invoke(Request $request)
    {
        try {
            $request->merge(['created_by' => $request->user()?->id]);

            $po = $this->poService->createDraftPO($request->all());

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
