<?php

declare(strict_types=1);

namespace Modules\V1\Finance\Enums;

enum TransactionCategoryEnum: string
{
    case PO = 'po';
    case KITCHEN_ORDER = 'kitchen_order';
    case ADJUSTMENT = 'adjustment';

    public function getLabel(): string
    {
        return match ($this) {
            self::PO => 'Purchase Order',
            self::KITCHEN_ORDER => 'Kitchen Order',
            self::ADJUSTMENT => 'Adjustment',
        };
    }
}
