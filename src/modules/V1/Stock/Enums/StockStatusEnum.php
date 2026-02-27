<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Enums;

enum StockStatusEnum: string
{
    case AVAILABLE = 'available';
    case ALLOCATED = 'allocated';
    case EXPIRED = 'expired';
}
