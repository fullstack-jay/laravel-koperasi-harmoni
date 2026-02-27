<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Service;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\V1\PurchaseOrder\Enums\POStatusEnum;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;
use Modules\V1\PurchaseOrder\Models\PurchaseOrderItem;

final class POService
{
    public function __construct(
        private POStatusService $statusService,
        private POCalculationService $calculationService
    ) {}

    public function createDraftPO(array $data): PurchaseOrder
    {
        DB::beginTransaction();

        try {
            $poNumber = $this->generatePONumber();

            $estimatedTotal = $this->calculationService->calculateEstimatedTotal($data['items']);

            $po = PurchaseOrder::create([
                'po_number' => $poNumber,
                'po_date' => $data['po_date'],
                'supplier_id' => $data['supplier_id'],
                'status' => POStatusEnum::DRAFT,
                'estimated_total' => $estimatedTotal,
                'estimated_delivery_date' => $data['estimated_delivery_date'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'item_id' => $item['item_id'],
                    'estimated_unit_price' => $item['estimated_unit_price'],
                    'estimated_qty' => $item['estimated_qty'],
                    'estimated_subtotal' => $this->calculationService->calculateItemSubtotal(
                        $item['estimated_unit_price'],
                        $item['estimated_qty']
                    ),
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            return $po->load('items');
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function sendToSupplier(string $poId, ?string $userId = null): PurchaseOrder
    {
        $po = PurchaseOrder::with('items')->find($poId);

        if (!$po) {
            throw new Exception('Purchase Order not found');
        }

        $this->statusService->transitionStatus(
            $po,
            POStatusEnum::TERKIRIM,
            'PO sent to supplier',
            $userId
        );

        return $po->fresh();
    }

    public function cancelPO(string $poId, string $reason, ?string $userId = null): PurchaseOrder
    {
        $po = PurchaseOrder::find($poId);

        if (!$po) {
            throw new Exception('Purchase Order not found');
        }

        if (!$this->statusService->canCancel($po)) {
            throw new Exception('Cannot cancel PO in current status');
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

    private function generatePONumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = "PO-{$date}";

        $lastPO = PurchaseOrder::where('po_number', 'like', "{$prefix}%")
            ->orderBy('po_number', 'desc')
            ->first();

        if ($lastPO) {
            $lastNumber = (int) Str::after($lastPO->po_number, "{$prefix}-");
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}-{$newNumber}";
    }
}
