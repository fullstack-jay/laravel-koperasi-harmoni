<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\V1\Stock\Enums\StockMovementTypeEnum;
use Modules\V1\Stock\Models\StockCard;
use Modules\V1\Stock\Models\StockItem;

class StockCardService
{
    /**
     * Record stock movement
     */
    public function recordMovement(array $data): StockCard
    {
        return DB::transaction(function () use ($data) {
            // Get current balance
            $lastBalance = StockCard::where('item_id', $data['item_id'])
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->value('balance') ?? 0;

            // Calculate new balance
            $qtyIn = $data['qty_in'] ?? 0;
            $qtyOut = $data['qty_out'] ?? 0;

            $balance = match ($data['type']) {
                StockMovementTypeEnum::IN->value => $lastBalance + $qtyIn,
                StockMovementTypeEnum::OUT->value => $lastBalance - $qtyOut,
                StockMovementTypeEnum::ADJUSTMENT->value,
                StockMovementTypeEnum::OPNAME->value => $data['balance'] ?? $lastBalance,
                default => $lastBalance,
            };

            $stockCard = StockCard::create([
                'item_id' => $data['item_id'],
                'batch_id' => $data['batch_id'] ?? null,
                'date' => $data['date'] ?? now()->format('Y-m-d'),
                'type' => $data['type'],
                'reference' => $data['reference'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'qty_in' => $qtyIn,
                'qty_out' => $qtyOut,
                'balance' => $balance,
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'],
            ]);

            // Update item's current stock
            $item = StockItem::find($data['item_id']);
            if ($item) {
                $item->update(['current_stock' => $balance]);
            }

            return $stockCard;
        });
    }

    /**
     * Get stock card by item ID
     */
    public function getStockCardByItem(string $itemId, ?string $startDate = null, ?string $endDate = null)
    {
        $query = StockCard::where('item_id', $itemId);

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        return $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get stock card by batch ID
     */
    public function getStockCardByBatch(string $batchId)
    {
        return StockCard::where('batch_id', $batchId)
            ->orderBy('date', 'desc')
            ->get();
    }
}
