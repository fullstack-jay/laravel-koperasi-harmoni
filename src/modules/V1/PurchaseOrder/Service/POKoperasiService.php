<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Service;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\V1\PurchaseOrder\Enums\POStatusEnum;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;
use Modules\V1\PurchaseOrder\Models\PurchaseOrderItem;
use Modules\V1\Stock\Models\StockBatch;
use Modules\V1\Stock\Models\StockCard;
use Modules\V1\Stock\Models\StockItem;
use Modules\V1\Stock\Enums\StockMovementTypeEnum;

final class POKoperasiService
{
    public function __construct(
        private POStatusService $statusService,
        private POCalculationService $calculationService
    ) {}

    public function confirmSupplierResponse(string $poId, ?string $userId = null): PurchaseOrder
    {
        $po = PurchaseOrder::find($poId);

        if (!$po) {
            throw new Exception('Purchase Order not found');
        }

        if ($po->status !== POStatusEnum::DIKONFIRMASI_SUPPLIER) {
            throw new Exception('PO must be in DIKONFIRMASI_SUPPLIER status');
        }

        $this->statusService->transitionStatus(
            $po,
            POStatusEnum::DIKONFIRMASI_KOPERASI,
            'Koperasi confirmed supplier response',
            $userId
        );

        return $po->fresh();
    }

    public function rejectSupplierResponse(string $poId, string $reason, ?string $userId = null): PurchaseOrder
    {
        $po = PurchaseOrder::find($poId);

        if (!$po) {
            throw new Exception('Purchase Order not found');
        }

        if ($po->status !== POStatusEnum::DIKONFIRMASI_SUPPLIER) {
            throw new Exception('PO must be in DIKONFIRMASI_SUPPLIER status');
        }

        $po->update(['rejection_reason' => $reason]);

        $this->statusService->transitionStatus(
            $po,
            POStatusEnum::DIBATALKAN,
            $reason,
            $userId
        );

        return $po->fresh();
    }

    public function receiveGoods(string $poId, array $data): array
    {
        DB::beginTransaction();

        try {
            $po = PurchaseOrder::with(['items', 'supplier'])->find($poId);

            if (!$po) {
                throw new Exception('Purchase Order not found');
            }

            if ($po->status !== POStatusEnum::DIKONFIRMASI_KOPERASI) {
                throw new Exception('PO must be in DIKONFIRMASI_KOPERASI status');
            }

            $batchNumber = $this->generateBatchNumber($po->supplier_id);

            foreach ($data['items'] as $receivedItem) {
                $poItem = PurchaseOrderItem::where('purchase_order_id', $po->id)
                    ->where('id', $receivedItem['item_id'])
                    ->first();

                if (!$poItem) {
                    throw new Exception('PO Item not found');
                }

                // Create stock_batch
                $batch = StockBatch::create([
                    'batch_number' => $batchNumber,
                    'item_id' => $poItem->item_id,
                    'supplier_id' => $po->supplier_id,
                    'purchase_price' => $poItem->actual_unit_price,
                    'qty' => $receivedItem['received_qty'],
                    'remaining_qty' => $receivedItem['received_qty'],
                    'expiry_date' => $receivedItem['expiry_date'] ?? null,
                    'production_date' => $receivedItem['production_date'] ?? null,
                    'received_date' => $data['received_date'] ?? now()->toDateString(),
                    'notes' => $receivedItem['notes'] ?? null,
                ]);

                // Update stock_item.current_stock
                $stockItem = StockItem::find($poItem->item_id);
                if ($stockItem) {
                    $stockItem->increment('current_stock', $receivedItem['received_qty']);
                }

                // Create stock_card entry (IN)
                StockCard::create([
                    'item_id' => $poItem->item_id,
                    'batch_id' => $batch->id,
                    'type' => StockMovementTypeEnum::IN,
                    'qty' => $receivedItem['received_qty'],
                    'unit_price' => $poItem->actual_unit_price,
                    'reference' => $po->po_number,
                    'reference_id' => $po->id,
                    'notes' => 'PO Receipt',
                ]);
            }

            // Update PO status
            $po->update([
                'actual_delivery_date' => $data['received_date'] ?? now()->toDateString(),
                'invoice_number' => $data['invoice_number'] ?? null,
            ]);

            $this->statusService->transitionStatus(
                $po,
                POStatusEnum::SELESAI,
                'Goods received',
                $data['received_by'] ?? null
            );

            // TODO: Create purchase transaction (will integrate with Finance later)

            DB::commit();

            return [
                'po' => $po->fresh()->load('items'),
                'batch_number' => $batchNumber,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function generateBatchNumber(string $supplierId): string
    {
        $date = now()->format('Ymd');
        $prefix = "BTH-{$supplierId}-{$date}";

        $lastBatch = StockBatch::where('batch_number', 'like', "{$prefix}%")
            ->orderBy('batch_number', 'desc')
            ->first();

        if ($lastBatch) {
            $lastNumber = (int) str_replace($prefix . '-', '', $lastBatch->batch_number);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return "{$prefix}-{$newNumber}";
    }
}
