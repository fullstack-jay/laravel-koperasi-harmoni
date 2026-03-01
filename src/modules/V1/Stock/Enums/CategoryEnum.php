<?php

declare(strict_types=1);

namespace Modules\V1\Stock\Enums;

enum CategoryEnum: string
{
    case BPO = 'BPO';           // Bahan Pokok
    case PHW = 'PHW';           // Protein Hewani
    case PNA = 'PNA';           // Protein Nabati
    case SYR = 'SYR';           // Sayuran
    case BUA = 'BUA';           // Buah
    case BMR = 'BMR';           // Bumbu & Rempah
    case BPN = 'BPN';           // Bahan Pendukung
    case KMN = 'KMN';           // Kemasan

    /**
     * Get full name from abbreviation
     */
    public function getFullName(): string
    {
        return match($this) {
            self::BPO => 'Bahan Pokok',
            self::PHW => 'Protein Hewani',
            self::PNA => 'Protein Nabati',
            self::SYR => 'Sayuran',
            self::BUA => 'Buah',
            self::BMR => 'Bumbu & Rempah',
            self::BPN => 'Bahan Pendukung',
            self::KMN => 'Kemasan',
        };
    }

    /**
     * Get full name from abbreviation string
     */
    public static function getFullNameByCode(string $code): string
    {
        $mapping = [
            'BPO' => 'BAHAN POKOK',
            'PHW' => 'PROTEIN HEWANI',
            'PNA' => 'PROTEIN NABATI',
            'SYR' => 'SAYURAN',
            'BUA' => 'BUAH',
            'BMR' => 'BUMBU & REMPAH',
            'BPN' => 'BAHAN PENDUKUNG',
            'KMN' => 'KEMASAN',
        ];

        return $mapping[strtoupper($code)] ?? $code;
    }

    /**
     * Get all available categories as key-value pairs
     */
    public static function getAll(): array
    {
        return [
            'BPO' => 'Bahan Pokok',
            'PHW' => 'Protein Hewani',
            'PNA' => 'Protein Nabati',
            'SYR' => 'Sayuran',
            'BUA' => 'Buah',
            'BMR' => 'Bumbu & Rempah',
            'BPN' => 'Bahan Pendukung',
            'KMN' => 'Kemasan',
        ];
    }
}
