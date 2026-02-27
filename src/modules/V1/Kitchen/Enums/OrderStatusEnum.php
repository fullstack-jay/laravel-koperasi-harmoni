<?php

declare(strict_types=1);

namespace Modules\V1\Kitchen\Enums;

enum OrderStatusEnum: string
{
    case DRAFT = 'draft';
    case TERKIRIM = 'terkirim';
    case DIPROSES = 'diproses';
    case DITERIMA_DAPUR = 'diterima_dapur';
    case DIBATALKAN = 'dibatalkan';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::TERKIRIM => 'Terkirim',
            self::DIPROSES => 'Diproses',
            self::DITERIMA_DAPUR => 'Diterima Dapur',
            self::DIBATALKAN => 'Dibatalkan',
        };
    }

    public function canTransitionTo(OrderStatusEnum $status): bool
    {
        return match ($this) {
            self::DRAFT => in_array($status, [self::TERKIRIM, self::DIBATALKAN]),
            self::TERKIRIM => in_array($status, [self::DIPROSES, self::DIBATALKAN]),
            self::DIPROSES => in_array($status, [self::DITERIMA_DAPUR, self::DIBATALKAN]),
            self::DITERIMA_DAPUR => false,
            self::DIBATALKAN => false,
        };
    }
}
