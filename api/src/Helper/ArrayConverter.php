<?php

declare(strict_types=1);

namespace App\Helper;

class ArrayConverter
{
    /**
     * @param mixed[] $array
     * @param string $separator
     * @return string
     */
    public function toStringOfValues(array $array, string $separator = ', '): string
    {
        $parts = array_map(fn($value) => $this->convertToString($value, $separator), $array);
        return '[' . implode($separator, $parts) . ']';
    }

    /**
     * Safely converts a mixed value to string.
     */
    private function convertToString(mixed $value, string $separator): string
    {
        return match (true) {
            is_array($value) => $this->toStringOfValues($value, $separator),
            is_object($value) => $this->toStringOfValues(get_object_vars($value), $separator),
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value), is_float($value), is_string($value) => (string) $value,
            default => '[unsupported]',
        };
    }
}
