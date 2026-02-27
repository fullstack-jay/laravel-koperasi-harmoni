<?php

declare(strict_types=1);

namespace Modules\V1\Kitchen\Service;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\V1\Kitchen\Enums\OrderStatusEnum;
use Modules\V1\Kitchen\Models\KitchenOrder;
use Modules\V1\Kitchen\Models\KitchenOrderItem;
use Modules\V1\Stock\Models\StockItem;

final class KitchenOrderService
{
    public function createDraftOrder(array $data): KitchenOrder
    {
        DB::beginTransaction();

        try {
            $orderNumber = $this->generateOrderNumber();

            $estimatedTotal = $this->calculateEstimatedTotal($data['items']);

            $order = KitchenOrder::create([
                'order_number' => $orderNumber,
                'order_date' => $data['order_date'],
                'dapur_id' => $data['dapur_id'],
                'status' => OrderStatusEnum::DRAFT,
                'estimated_total' => $estimatedTotal,
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $stockItem = StockItem::find($item['item_id']);

                if (!$stockItem) {
                    throw new Exception('Stock item not found');
                }

                KitchenOrderItem::create([
                    'kitchen_order_id' => $order->id,
                    'item_id' => $item['item_id'],
                    'requested_qty' => $item['requested_qty'],
                    'unit_price' => $stockItem->sell_price,
                    'subtotal' => $stockItem->sell_price * $item['requested_qty'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            return $order->load('items');
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function sendOrder(string $orderId, ?string $userId = null): KitchenOrder
    {
        $order = KitchenOrder::find($orderId);

        if (!$order) {
            throw new Exception('Order not found');
        }

        if ($order->status !== OrderStatusEnum::DRAFT) {
            throw new Exception('Order must be in DRAFT status');
        }

        $order->update([
            'status' => OrderStatusEnum::TERKIRIM,
            'sent_at' => now(),
        ]);

        return $order->fresh();
    }

    private function calculateEstimatedTotal(array $items): float
    {
        return collect($items)->sum(function ($item) {
            return ($item['unit_price'] ?? 0) * $item['requested_qty'];
        });
    }

    private function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = "ORD-{$date}";

        $lastOrder = KitchenOrder::where('order_number', 'like', "{$prefix}%")
            ->orderBy('order_number', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) Str::after($lastOrder->order_number, "{$prefix}-");
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}-{$newNumber}";
    }
}
