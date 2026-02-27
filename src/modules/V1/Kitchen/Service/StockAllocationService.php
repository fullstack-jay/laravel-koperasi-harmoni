<?php

declare(strict_types=1);

namespace Modules\V1\Kitchen\Service;

use Modules\V1\Stock\Services\FEFOService;

final class StockAllocationService
{
    public function __construct(
        private FEFOService $fefoService
    ) {}

    public function allocateStock(string $itemId, int $qty): ?array
    {
        return $this->fefoService->allocateStock($itemId, $qty);
    }

    public function checkAvailability(array $items): array
    {
        return $this->fefoService->checkStockAvailability($items);
    }

    public function confirmAllocation(array $allocations): bool
    {
        return $this->fefoService->confirmAllocation($allocations);
    }

    public function getAvailableStock(string $itemId): int
    {
        return $this->fefoService->getAvailableStock($itemId);
    }
}
