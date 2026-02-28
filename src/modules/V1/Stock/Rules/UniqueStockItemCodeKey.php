<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\V1\Stock\Models\StockItem;

class UniqueStockItemCodeKey implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Extract the key part (everything after the first hyphen)
        // Example: BPO-BRS-PRM-25KG → BRS-PRM-25KG
        //          BPL-BRS-PRM → BRS-PRM
        $parts = explode('-', $value);

        if (count($parts) < 2) {
            $fail('The :attribute format is invalid. Format: CATEGORY-PROD-TYPE[-SIZE]');

            return;
        }

        // Remove category (first part) and get the key
        $inputKey = implode('-', array_slice($parts, 1));

        // Get all existing stock items and check if any has the same product key
        $existingItems = StockItem::all(['code']);

        foreach ($existingItems as $item) {
            $itemParts = explode('-', $item->code);

            if (count($itemParts) >= 2) {
                // Extract key from existing item
                $itemKey = implode('-', array_slice($itemParts, 1));

                // Check if keys match
                if ($itemKey === $inputKey) {
                    $fail("The product key '{$inputKey}' already exists in code '{$item->code}'. Please use a different product-type combination.");

                    return;
                }
            }
        }
    }
}
