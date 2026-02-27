<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Service;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\V1\PurchaseOrder\Enums\POStatusEnum;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;
use Modules\V1\PurchaseOrder\Models\PurchaseOrderItem;

final class POSupplierService
{
    public function __construct(
        private POStatusService $statusService,
        private POCalculationService $calculationService
    ) {}

    public function confirmPO(string $poId, array $items, ?string $userId = null): PurchaseOrder
    {
        DB::beginTransaction();

        try {
            $po = PurchaseOrder::with('items')->find($poId);

            if (!$po) {
                throw new Exception('Purchase Order not found');
            }

            if ($po->status !== POStatusEnum::TERKIRIM) {
                throw new Exception('PO must be in TERKIRIM status');
            }

            foreach ($items as $itemData) {
                $item = PurchaseOrderItem::where('purchase_order_id', $po->id)
                    ->where('id', $itemData['item_id'])
                    ->first();

                if (!$item) {
                    throw new Exception('Item not found');
                }

                $item->update([
                    'actual_unit_price' => $itemData['actual_unit_price'],
                    'actual_qty' => $itemData['actual_qty'],
                    'actual_subtotal' => $this->calculationService->calculateItemSubtotal(
                        $itemData['actual_unit_price'],
                        $itemData['actual_qty']
                    ),
                ]);
            }

            $actualTotal = $this->calculationService->calculateActualTotal(
                $po->items->toArray()
            );

            $po->update(['actual_total' => $actualTotal]);

            $this->statusService->transitionStatus(
                $po,
                POStatusEnum::DIKONFIRMASI_SUPPLIER,
                'Supplier confirmed PO',
                $userId
            );

            DB::commit();

            return $po->fresh()->load('items');
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function rejectPO(string $poId, string $reason, ?string $userId = null): PurchaseOrder
    {
        $po = PurchaseOrder::find($poId);

        if (!$po) {
            throw new Exception('Purchase Order not found');
        }

        if ($po->status !== POStatusEnum::TERKIRIM) {
            throw new Exception('PO must be in TERKIRIM status');
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
}
