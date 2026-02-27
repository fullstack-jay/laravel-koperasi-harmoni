<?php

declare(strict_types=1);

namespace Modules\V1\Kitchen\Service;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\V1\Kitchen\Enums\OrderStatusEnum;
use Modules\V1\Kitchen\Models\KitchenOrder;
use Modules\V1\Kitchen\Models\KitchenOrderItem;
use Modules\V1\Kitchen\Models\SuratJalan;
use Modules\V1\QRCode\Service\QRCodeService;
use Modules\V1\Stock\Models\StockBatch;
use Modules\V1\Stock\Models\StockCard;
use Modules\V1\Stock\Models\StockItem;
use Modules\V1\Stock\Enums\StockMovementTypeEnum;
use Modules\V1\Finance\Service\TransactionService;

final class DeliveryService
{
    public function __construct(
        private QRCodeService $qrCodeService,
        private TransactionService $transactionService,
        private SuratJalanService $suratJalanService
    ) {}

    public function deliverOrder(string $orderId, array $data): array
    {
        DB::beginTransaction();

        try {
            $order = KitchenOrder::with(['items', 'dapur'])->find($orderId);

            if (!$order) {
                throw new Exception('Order not found');
            }

            if ($order->status !== OrderStatusEnum::DIPROSES) {
                throw new Exception('Order must be in DIPROSES status');
            }

            // Process each item
            foreach ($order->items as $orderItem) {
                if (!$orderItem->approved_qty || !$orderItem->stock_allocations) {
                    continue;
                }

                // Reduce stock from allocated batches
                foreach ($orderItem->stock_allocations as $allocation) {
                    $batch = StockBatch::find($allocation['batchId']);

                    if (!$batch) {
                        throw new Exception("Batch {$allocation['batchId']} not found");
                    }

                    // Update remaining qty
                    $batch->remaining_qty -= $allocation['qty'];
                    if ($batch->remaining_qty <= 0) {
                        $batch->status = 'allocated';
                        $batch->remaining_qty = 0;
                    }
                    $batch->save();

                    // Create stock_card entry (OUT)
                    StockCard::create([
                        'item_id' => $orderItem->item_id,
                        'batch_id' => $batch->id,
                        'type' => StockMovementTypeEnum::OUT,
                        'qty' => $allocation['qty'],
                        'unit_price' => $allocation['buyPrice'],
                        'reference' => $order->order_number,
                        'reference_id' => $order->id,
                        'notes' => 'Kitchen Order Delivery',
                    ]);
                }

                // Update stock_item current stock
                $stockItem = StockItem::find($orderItem->item_id);
                if ($stockItem) {
                    $stockItem->decrement('current_stock', $orderItem->approved_qty);
                }
            }

            // Generate QR code
            $orderData = $order->items->map(fn($item) => [
                'item_id' => $item->item_id,
                'qty' => $item->approved_qty,
                'unit_price' => $item->unit_price,
            ])->toArray();

            $qrResult = $this->qrCodeService->generateDeliveryQR($order->id, $orderData);

            // Create Surat Jalan
            $suratJalan = $this->suratJalanService->create($order->id, $data);

            // Record SALES transaction
            $transaction = $this->transactionService->recordSales(
                $order,
                $order->items->pluck('stock_allocations')->flatten()->toArray()
            );

            // Update order status
            $order->update([
                'status' => OrderStatusEnum::DITERIMA_DAPUR,
                'delivered_at' => now(),
                'received_at' => now(),
                'qr_code' => $qrResult['qrString'],
            ]);

            DB::commit();

            return [
                'order' => $order->fresh()->load('items'),
                'qr_code' => $qrResult['qrString'],
                'qr_image_url' => $qrResult['imageUrl'],
                'surat_jalan' => $suratJalan,
                'transaction' => $transaction,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
