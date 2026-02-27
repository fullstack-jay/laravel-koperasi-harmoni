<?php

declare(strict_types=1);

namespace Modules\V1\Finance\Service;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\V1\Finance\Enums\TransactionCategoryEnum;
use Modules\V1\Finance\Enums\PaymentStatusEnum;
use Modules\V1\Finance\Enums\TransactionTypeEnum;
use Modules\V1\Finance\Models\Transaction;
use Modules\V1\Finance\Models\TransactionItem;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;
use Modules\V1\Kitchen\Models\KitchenOrder;

final class TransactionService
{
    public function __construct(
        private ProfitCalculationService $profitService
    ) {}

    public function recordPurchase(PurchaseOrder $po): Transaction
    {
        DB::beginTransaction();

        try {
            $transaction = Transaction::create([
                'date' => $po->actual_delivery_date ?? now()->toDateString(),
                'type' => TransactionTypeEnum::PURCHASE,
                'category' => TransactionCategoryEnum::PO,
                'amount' => $po->actual_total ?? $po->estimated_total,
                'profit' => 0,
                'margin' => 0,
                'reference' => $po->po_number,
                'reference_id' => $po->id,
                'supplier_id' => $po->supplier_id,
                'items' => $po->items->map(fn($item) => [
                    'item_id' => $item->item_id,
                    'qty' => $item->actual_qty ?? $item->estimated_qty,
                    'unit_price' => $item->actual_unit_price ?? $item->estimated_unit_price,
                    'subtotal' => $item->actual_subtotal ?? $item->estimated_subtotal,
                ])->toArray(),
                'payment_status' => PaymentStatusEnum::PENDING,
                'created_by' => $po->created_by,
            ]);

            // Create transaction items
            foreach ($po->items as $poItem) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'item_id' => $poItem->item_id,
                    'qty' => $poItem->actual_qty ?? $poItem->estimated_qty,
                    'buy_price' => $poItem->actual_unit_price ?? $poItem->estimated_unit_price,
                    'sell_price' => 0, // Purchase doesn't have sell price
                    'subtotal' => $poItem->actual_subtotal ?? $poItem->estimated_subtotal,
                    'profit' => 0,
                    'margin' => 0,
                ]);
            }

            DB::commit();

            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function recordSales(KitchenOrder $order, array $fefoAllocations): Transaction
    {
        DB::beginTransaction();

        try {
            $totalProfit = 0;
            $totalRevenue = 0;

            $transaction = Transaction::create([
                'date' => now()->toDateString(),
                'type' => TransactionTypeEnum::SALES,
                'category' => TransactionCategoryEnum::KITCHEN_ORDER,
                'amount' => $order->actual_total,
                'reference' => $order->order_number,
                'reference_id' => $order->id,
                'dapur_id' => $order->dapur_id,
                'items' => $order->items->map(fn($item) => [
                    'item_id' => $item->item_id,
                    'qty' => $item->approved_qty,
                    'buy_price' => $item->buy_price,
                    'sell_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                    'profit' => $item->profit,
                ])->toArray(),
                'payment_status' => PaymentStatusEnum::PENDING,
                'created_by' => $order->created_by,
            ]);

            // Create transaction items with FEFO allocations
            foreach ($order->items as $orderItem) {
                if (!$orderItem->approved_qty) {
                    continue;
                }

                $buyPrice = $orderItem->buy_price;
                $sellPrice = $orderItem->unit_price;
                $qty = $orderItem->approved_qty;

                $profitCalc = $this->profitService->calculateItemProfit($sellPrice, $buyPrice, $qty);

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'item_id' => $orderItem->item_id,
                    'qty' => $qty,
                    'buy_price' => $buyPrice,
                    'sell_price' => $sellPrice,
                    'subtotal' => $profitCalc['revenue'],
                    'profit' => $profitCalc['profit'],
                    'margin' => $profitCalc['margin'],
                    'batch_details' => $orderItem->stock_allocations ?? [],
                ]);

                $totalProfit += $profitCalc['profit'];
                $totalRevenue += $profitCalc['revenue'];
            }

            // Update transaction totals
            $transaction->update([
                'profit' => $totalProfit,
                'margin' => $this->profitService->calculateTransactionMargin($totalRevenue, $totalProfit),
            ]);

            DB::commit();

            return $transaction->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function markAsPaid(string $transactionId, ?string $paymentDate = null): Transaction
    {
        $transaction = Transaction::find($transactionId);

        if (!$transaction) {
            throw new Exception('Transaction not found');
        }

        $transaction->update([
            'payment_status' => PaymentStatusEnum::PAID,
            'payment_date' => $paymentDate ?? now()->toDateString(),
        ]);

        return $transaction->fresh();
    }
}
