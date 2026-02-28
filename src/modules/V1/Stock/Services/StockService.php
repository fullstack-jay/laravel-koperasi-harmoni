<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Services;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Modules\V1\Stock\Models\StockItem;

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
        // Extract category from code (first part before hyphen)
        // Example: BPO-BRS-PRM-25KG → BPO
        $parts = explode('-', $data['code']);
        $category = $data['category'] ?? $parts[0] ?? null;

        return StockItem::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'category' => $category,
            'unit' => $data['unit'] ?? null,
            'min_stock' => $data['min_stock'] ?? 10,
            'max_stock' => $data['max_stock'] ?? 100,
            'buy_price' => $data['buy_price'] ?? 0,
            'sell_price' => $data['sell_price'] ?? 0,
            'supplier_id' => $data['supplier_id'] ?? null,
            'current_stock' => 0,
        ]);
    }

    /**
     * Update stock item
     */
    public function updateItem(string $id, array $data): StockItem
    {
        $item = $this->findItem($id);

        // Extract category from code if code is being updated
        $category = $data['category'] ?? $item->category;
        if (isset($data['code'])) {
            $parts = explode('-', $data['code']);
            $category = $parts[0] ?? $category;
        }

        $updateData = [
            'name' => $data['name'] ?? $item->name,
            'category' => $category,
            'unit' => $data['unit'] ?? $item->unit,
            'min_stock' => $data['min_stock'] ?? $item->min_stock,
            'max_stock' => $data['max_stock'] ?? $item->max_stock,
            'buy_price' => $data['buy_price'] ?? $item->buy_price,
            'sell_price' => $data['sell_price'] ?? $item->sell_price,
            'supplier_id' => $data['supplier_id'] ?? $item->supplier_id,
        ];

        // Include code in update if provided
        if (isset($data['code'])) {
            $updateData['code'] = $data['code'];
        }

        $item->update($updateData);

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
        string $search = '',
        ?string $supplierId = null
    ): Collection {
        $query = StockItem::query();

        // Filter by supplier
        if (!empty($supplierId)) {
            $query->where('supplier_id', $supplierId);
        }

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
