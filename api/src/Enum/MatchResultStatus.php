<?php

declare(strict_types=1);

namespace App\Enum;

enum MatchResultStatus: string
{
    case WIN = 'win';
    case LOSS = 'loss';
    case DRAW = 'draw';
    case UNKNOWN = 'unknown';

    public function getLabel(): string
    {
        return match ($this) {
            self::WIN => 'win',
            self::LOSS => 'loss',
            self::DRAW => 'draw',
            self::UNKNOWN => 'unknown',
        };
    }

    /**
     * Zwraca wszystkie wartości enuma
     * @return array<string>
     */
    public static function getValues(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }
}
