<?php

declare(strict_types=1);

namespace Modules\V1\Supplier\Enums;

enum SupplierItemStatusEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Aktif',
            self::INACTIVE => 'Tidak Aktif',
        };
    }
}
