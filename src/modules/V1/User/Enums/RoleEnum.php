<?php

declare(strict_types=1);

namespace Modules\V1\User\Enums;

use Shared\Helpers\StringHelper;

enum RoleEnum: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case ADMIN_PEMASOK = 'ADMIN_PEMASOK';
    case KEUANGAN = 'KEUANGAN';
    case PEMASOK = 'PEMASOK';
    case KOPERASI = 'KOPERASI';
    case DAPUR = 'DAPUR';

    public static function names(): array
    {
        return array_map(fn (RoleEnum $roles) => StringHelper::toTitleCase($roles->name), self::cases());
    }

    /**
     * Convert enum value to slug format (lowercase with underscores)
     */
    public function toSlug(): string
    {
        return strtolower($this->value);
    }
}
