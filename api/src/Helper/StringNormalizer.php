<?php

declare(strict_types=1);

namespace App\Helper;

final class StringNormalizer
{
    /**
     * Trims the value and collapses every run of whitespace into a single space,
     * so " Real  Madrid " and "Real Madrid" are stored (and compared for uniqueness) the same way.
     */
    public static function normalizeWhitespace(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }
}
