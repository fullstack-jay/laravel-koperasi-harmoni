<?php

declare(strict_types=1);

namespace Modules\V1\Supplier\Enums;

enum SupplierStatusEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
}
