<?php

declare(strict_types=1);

namespace Modules\V1\Finance\Enums;

enum TransactionTypeEnum: string
{
    case PURCHASE = 'purchase';
    case SALES = 'sales';

    public function getLabel(): string
    {
        return match ($this) {
            self::PURCHASE => 'Purchase',
            self::SALES => 'Sales',
        };
    }
}
