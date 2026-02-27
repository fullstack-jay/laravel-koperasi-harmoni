<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Services;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Modules\V1\Stock\Models\StockItem;
use Shared\Helpers\DocumentHelper;

class StockService
{
    public function __construct(
        private FEFOService $fefaService
    ) {
    }

    /**
     * Create a new stock item
     */
    public function createItem(array $data): StockItem
    {
        // Generate stock item code
        $lastItem = StockItem::withTrashed()->orderBy('code', 'desc')->first();
        $sequence = $lastItem ? (int) str_replace('STK-', '', $lastItem->code) + 1 : 1;
        $code = DocumentHelper::generateStockItemCode($sequence);

        return StockItem::create([
            'code' => $code,
            'name' => $data['name'],
            'category' => $data['category'] ?? null,
            'unit' => $data['unit'] ?? null,
            'min_stock' => $data['min_stock'] ?? 10,
            'buy_price' => $data['buy_price'] ?? 0,
            'sell_price' => $data['sell_price'] ?? 0,
            'current_stock' => 0,
        ]);
    }

    /**
     * Update stock item
     */
    public function updateItem(string $id, array $data): StockItem
    {
        $item = $this->findItem($id);

        $item->update([
            'name' => $data['name'] ?? $item->name,
            'category' => $data['category'] ?? $item->category,
            'unit' => $data['unit'] ?? $item->unit,
            'min_stock' => $data['min_stock'] ?? $item->min_stock,
            'buy_price' => $data['buy_price'] ?? $item->buy_price,
            'sell_price' => $data['sell_price'] ?? $item->sell_price,
        ]);

        return $item->fresh();
    }

    /**
     * Get all stock items with pagination
     */
    public function getAllItems(
        int $pageNumber = 1,
        int $pageSize = 15,
        string $sortColumn = 'created_at',
        string $sortDir = 'desc',
        string $search = ''
    ): Collection {
        $query = StockItem::query();

        // Apply global search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('code', 'ILIKE', "%{$search}%")
                  ->orWhere('category', 'ILIKE', "%{$search}%");
            });
        }

        // Apply sorting and pagination
        $offset = ($pageNumber - 1) * $pageSize;

        return $query->orderBy($sortColumn, $sortDir)
                     ->offset($offset)
                     ->limit($pageSize)
                     ->get();
    }

    /**
     * Find stock item by ID
     */
    public function findItem(string $id): StockItem
    {
        $item = StockItem::find($id);

        if (! $item) {
            throw new Exception('Stock item not found', 404);
        }

        return $item;
    }

    /**
     * Delete stock item
     */
    public function deleteItem(string $id): bool
    {
        $item = $this->findItem($id);

        return $item->delete();
    }

    /**
     * Get available stock for item
     */
    public function getAvailableStock(string $itemId): int
    {
        return $this->fefaService->getAvailableStock($itemId);
    }
}
