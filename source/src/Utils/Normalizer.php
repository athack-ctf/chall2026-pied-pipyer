<?php

namespace App\Utils;

class Normalizer
{
    public static function normalizeName(string $name): string
    {
        $normalized = strtolower($name);
        $normalized = preg_replace('/[-_.]+/', '-', $normalized);
        return $normalized;
    }

    public static function isNormalized(string $name): bool
    {
        return $name === self::normalizeName($name);
    }
}
