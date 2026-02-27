<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Enums;

enum AlertTypeEnum: string
{
    case LOW_STOCK = 'low_stock';
    case OUT_OF_STOCK = 'out_of_stock';
    case EXPIRED = 'expired';
    case EXPIRING_SOON = 'expiring_soon';
}
