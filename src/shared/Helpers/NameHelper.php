<?php

declare(strict_types=1);

namespace Shared\Helpers;

class NameHelper
{
    /**
     * Split full name into first and last name
     *
     * @param string $fullName
     * @return array{first_name: string, last_name: string}
     */
    public static function splitFullName(string $fullName): array
    {
        $trimmedName = trim($fullName);
        $nameParts = explode(' ', $trimmedName, 2);

        return [
            'first_name' => $nameParts[0],
            'last_name' => $nameParts[1] ?? '',
        ];
    }

    /**
     * Get first name from full name
     *
     * @param string $fullName
     * @return string
     */
    public static function getFirstName(string $fullName): string
    {
        return self::splitFullName($fullName)['first_name'];
    }

    /**
     * Get last name from full name
     *
     * @param string $fullName
     * @return string
     */
    public static function getLastName(string $fullName): string
    {
        return self::splitFullName($fullName)['last_name'];
    }
}
