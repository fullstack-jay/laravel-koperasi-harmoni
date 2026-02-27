<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Controllers;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\V1\Stock\Enums\StockMovementTypeEnum;
use Modules\V1\Stock\Models\StockBatch;
use Modules\V1\Stock\Requests\StockAdjustmentRequest;
use Modules\V1\Stock\Services\FEFOService;
use Modules\V1\Stock\Services\StockCardService;
use Modules\V1\Stock\Services\StockService;
use Shared\Helpers\ResponseHelper;

final class StockAdjustmentController
{
    public function __construct(
        private StockCardService $stockCardService,
        private FEFOService $fefoService,
        private StockService $stockService
    ) {
    }

    /**
     * @OA\Post(
     *     path="/stock/adjust",
     *     summary="Record stock adjustment",
     *     description="Record stock movements (IN, OUT, ADJUSTMENT, OPNAME) with FEFO allocation for OUT movements",
     *     tags={"Stock"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 required={"item_id", "type", "quantity"},
     *
     *                 @OA\Property(property="item_id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="batch_id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174001", description="Required for IN type"),
     *                 @OA\Property(property="type", type="string", enum={"IN", "OUT", "ADJUSTMENT", "OPNAME"}, example="OUT"),
     *                 @OA\Property(property="quantity", type="integer", example=10, description="Quantity to adjust"),
     *                 @OA\Property(property="reference", type="string", example="ADJ", description="Reference code"),
     *                 @OA\Property(property="reference_id", type="string", example="REF-001"),
     *                 @OA\Property(property="notes", type="string", example="Stock adjustment notes")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Stock adjustment recorded successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Stock adjustment recorded successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Insufficient stock or invalid request"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function adjust(StockAdjustmentRequest $request)
    {
        return DB::transaction(function () use ($request) {
            try {
                $data = $request->validated();
                $type = $data['type'];
                $quantity = $data['quantity'];
                $itemId = $data['item_id'];
                $batchId = $data['batch_id'] ?? null;

                // For OUT type, use FEFO to allocate stock
                if ($type === StockMovementTypeEnum::OUT->value) {
                    $availableStock = $this->fefoService->getAvailableStock($itemId);

                    if ($availableStock < $quantity) {
                        return ResponseHelper::error(
                            message: 'Insufficient stock available',
                            status: 400
                        );
                    }

                    // Allocate using FEFO
                    $selectedBatches = $this->fefoService->allocateStock($itemId, $quantity);

                    if (! $selectedBatches) {
                        return ResponseHelper::error(
                            message: 'Failed to allocate stock',
                            status: 400
                        );
                    }

                    // Confirm allocation
                    $this->fefoService->confirmAllocation($selectedBatches);

                    // Record stock card for each batch
                    foreach ($selectedBatches as $batch) {
                        $this->stockCardService->recordMovement([
                            'item_id' => $itemId,
                            'batch_id' => $batch['batchId'],
                            'type' => $type,
                            'qty_in' => 0,
                            'qty_out' => $batch['qty'],
                            'reference' => $data['reference'] ?? 'ADJ',
                            'reference_id' => $data['reference_id'] ?? null,
                            'notes' => $data['notes'] ?? null,
                            'created_by' => auth()->id(),
                        ]);
                    }
                } elseif ($type === StockMovementTypeEnum::IN->value) {
                    // For IN type, add to specified batch or create adjustment
                    if ($batchId) {
                        $batch = StockBatch::find($batchId);
                        if (! $batch) {
                            throw new Exception('Batch not found', 404);
                        }

                        $batch->remaining_qty += $quantity;
                        $batch->save();

                        $this->stockCardService->recordMovement([
                            'item_id' => $itemId,
                            'batch_id' => $batchId,
                            'type' => $type,
                            'qty_in' => $quantity,
                            'qty_out' => 0,
                            'reference' => $data['reference'] ?? 'ADJ',
                            'reference_id' => $data['reference_id'] ?? null,
                            'notes' => $data['notes'] ?? null,
                            'created_by' => auth()->id(),
                        ]);
                    } else {
                        throw new Exception('Batch ID is required for IN type', 400);
                    }
                } elseif ($type === StockMovementTypeEnum::ADJUSTMENT->value
                    || $type === StockMovementTypeEnum::OPNAME->value) {
                    // For adjustment or opname, just record the movement
                    $item = $this->stockService->findItem($itemId);

                    $this->stockCardService->recordMovement([
                        'item_id' => $itemId,
                        'batch_id' => $batchId,
                        'type' => $type,
                        'qty_in' => $type === StockMovementTypeEnum::ADJUSTMENT->value ? $quantity : 0,
                        'qty_out' => 0,
                        'balance' => $type === StockMovementTypeEnum::OPNAME->value ? $quantity : null,
                        'reference' => $data['reference'] ?? 'OPNAME',
                        'reference_id' => $data['reference_id'] ?? null,
                        'notes' => $data['notes'] ?? null,
                        'created_by' => auth()->id(),
                    ]);
                }

                return ResponseHelper::success(
                    message: 'Stock adjustment recorded successfully'
                );
            } catch (Exception $e) {
                return ResponseHelper::error(
                    message: $e->getMessage() ?? 'Failed to record stock adjustment',
                    status: $e->getCode() >= 400 ? $e->getCode() : 500
                );
            }
        });
    }
}
