<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Enums;

enum StockMovementTypeEnum: string
{
    case IN = 'in';
    case OUT = 'out';
    case ADJUSTMENT = 'adjustment';
    case OPNAME = 'opname';
}
