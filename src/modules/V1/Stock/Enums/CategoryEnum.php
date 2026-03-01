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
            'BPO' => 'BAHAN POKOK',
            'PHW' => 'PROTEIN HEWANI',
            'PNA' => 'PROTEIN NABATI',
            'SYR' => 'SAYURAN',
            'BUA' => 'BUAH',
            'BMR' => 'BUMBU & REMPAH',
            'BPN' => 'BAHAN PENDUKUNG',
            'KMN' => 'KEMASAN',
        ];
    }

    /**
     * Get code from full name (reverse mapping)
     * Example: "BAHAN POKOK" → "BPO"
     */
    public static function getCodeByFullName(string $fullName): ?string
    {
        $all = self::getAll();

        // Case-insensitive search
        foreach ($all as $code => $name) {
            if (strtoupper($fullName) === strtoupper($name)) {
                return $code;
            }
        }

        // If input is already a code, return it
        if (array_key_exists(strtoupper($fullName), array_change_key_case(array_combine(array_keys($all), array_keys($all)), CASE_UPPER))) {
            return strtoupper($fullName);
        }

        return null;
    }
}
