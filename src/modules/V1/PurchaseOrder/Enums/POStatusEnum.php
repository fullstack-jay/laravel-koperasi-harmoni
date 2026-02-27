<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Enums;

enum POStatusEnum: string
{
    case DRAFT = 'draft';
    case TERKIRIM = 'terkirim';
    case DIKONFIRMASI_SUPPLIER = 'dikonfirmasi_supplier';
    case DIKONFIRMASI_KOPERASI = 'dikonfirmasi_koperasi';
    case SELESAI = 'selesai';
    case DIBATALKAN = 'dibatalkan';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::TERKIRIM => 'Terkirim',
            self::DIKONFIRMASI_SUPPLIER => 'Dikonfirmasi Supplier',
            self::DIKONFIRMASI_KOPERASI => 'Dikonfirmasi Koperasi',
            self::SELESAI => 'Selesai',
            self::DIBATALKAN => 'Dibatalkan',
        };
    }

    public function canTransitionTo(POStatusEnum $status): bool
    {
        return match ($this) {
            self::DRAFT => in_array($status, [self::TERKIRIM, self::DIBATALKAN]),
            self::TERKIRIM => in_array($status, [self::DIKONFIRMASI_SUPPLIER, self::DIBATALKAN]),
            self::DIKONFIRMASI_SUPPLIER => in_array($status, [self::DIKONFIRMASI_KOPERASI, self::DIBATALKAN]),
            self::DIKONFIRMASI_KOPERASI => in_array($status, [self::SELESAI, self::DIBATALKAN]),
            self::SELESAI => false,
            self::DIBATALKAN => false,
        };
    }
}
