<?php

declare(strict_types=1);

namespace Modules\V1\Kitchen\Service;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\V1\Kitchen\Enums\OrderStatusEnum;
use Modules\V1\Kitchen\Models\KitchenOrder;
use Modules\V1\Kitchen\Models\KitchenOrderItem;
use Modules\V1\Stock\Services\FEFOService;

final class OrderProcessingService
{
    public function __construct(
        private FEFOService $fefoService,
        private StockAllocationService $allocationService
    ) {}

    public function processOrder(string $orderId, array $approvedItems): array
    {
        DB::beginTransaction();

        try {
            $order = KitchenOrder::with(['items', 'dapur'])->find($orderId);

            if (!$order) {
                throw new Exception('Order not found');
            }

            if ($order->status !== OrderStatusEnum::TERKIRIM) {
                throw new Exception('Order must be in TERKIRIM status');
            }

            $actualTotal = 0;

            foreach ($approvedItems as $approvedItem) {
                $orderItem = KitchenOrderItem::where('kitchen_order_id', $order->id)
                    ->where('id', $approvedItem['item_id'])
                    ->first();

                if (!$orderItem) {
                    throw new Exception('Order item not found');
                }

                // Use FEFO to allocate stock
                $allocations = $this->fefoService->allocateStock(
                    $orderItem->item_id,
                    $approvedItem['approved_qty']
                );

                if (!$allocations) {
                    throw new Exception(
                        "Insufficient stock for item: {$orderItem->item_id}. " .
                        "Required: {$approvedItem['approved_qty']}"
                    );
                }

                // Calculate weighted average buy price from allocations
                $buyPrice = $this->calculateWeightedAverageBuyPrice($allocations);

                // Calculate profit
                $subtotal = $orderItem->unit_price * $approvedItem['approved_qty'];
                $totalCost = $buyPrice * $approvedItem['approved_qty'];
                $profit = $subtotal - $totalCost;

                // Update order item
                $orderItem->update([
                    'approved_qty' => $approvedItem['approved_qty'],
                    'subtotal' => $subtotal,
                    'buy_price' => $buyPrice,
                    'profit' => $profit,
                    'stock_allocations' => $allocations,
                ]);

                $actualTotal += $subtotal;
            }

            // Update order status
            $order->update([
                'status' => OrderStatusEnum::DIPROSES,
                'actual_total' => $actualTotal,
                'processed_at' => now(),
            ]);

            DB::commit();

            return [
                'order' => $order->fresh()->load('items'),
                'allocations' => $approvedItems,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function calculateWeightedAverageBuyPrice(array $batches): float
    {
        $totalCost = 0;
        $totalQty = 0;

        foreach ($batches as $batch) {
            $totalCost += $batch['buyPrice'] * $batch['qty'];
            $totalQty += $batch['qty'];
        }

        return $totalQty > 0 ? $totalCost / $totalQty : 0;
    }
}
