<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Enums;

enum POStatusEnum: string
{
    case DRAFT = 'draft';
    case DIBATALKAN_DRAFT = 'dibatalkan_draft';
    case TERKIRIM = 'terkirim';
    case PERUBAHAN_HARGA = 'perubahan_harga';
    case DIKONFIRMASI_SUPPLIER = 'dikonfirmasi_supplier';
    case DIKONFIRMASI_KOPERASI = 'dikonfirmasi_koperasi';
    case SELESAI = 'selesai';
    case DIBATALKAN_KOPERASI = 'dibatalkan_koperasi';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::DIBATALKAN_DRAFT => 'Draft (Dibatalkan)',
            self::TERKIRIM => 'Terkirim',
            self::PERUBAHAN_HARGA => 'Perubahan Harga',
            self::DIKONFIRMASI_SUPPLIER => 'Dikonfirmasi Supplier',
            self::DIKONFIRMASI_KOPERASI => 'Dikonfirmasi Koperasi',
            self::SELESAI => 'Selesai',
            self::DIBATALKAN_KOPERASI => 'Dibatalkan Koperasi',
        };
    }

    public function canTransitionTo(POStatusEnum $status): bool
    {
        return match ($this) {
            self::DRAFT => in_array($status, [self::TERKIRIM, self::DIBATALKAN_DRAFT]),
            self::DIBATALKAN_DRAFT => in_array($status, [self::TERKIRIM]), // Cancelled draft can be re-sent
            self::TERKIRIM => in_array($status, [self::PERUBAHAN_HARGA, self::DIKONFIRMASI_SUPPLIER, self::DIBATALKAN_DRAFT]),
            self::PERUBAHAN_HARGA => in_array($status, [self::TERKIRIM, self::DIKONFIRMASI_SUPPLIER, self::DIBATALKAN_KOPERASI]),
            self::DIKONFIRMASI_SUPPLIER => in_array($status, [self::DIKONFIRMASI_KOPERASI]),
            self::DIKONFIRMASI_KOPERASI => in_array($status, [self::SELESAI]),
            self::SELESAI => false,
            self::DIBATALKAN_KOPERASI => false, // Cannot transition from cancelled by koperasi
        };
    }
}
