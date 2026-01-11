<?php

declare(strict_types=1);

namespace App\Enum;

enum MatchPoint
{
    case WIN;
    case DRAW;
    case LOSS;
    case UNKNOWN;

    public function points(): int
    {
        return match ($this) {
            self::WIN => 3,
            self::DRAW => 1,
            self::LOSS, self::UNKNOWN => 0,
        };
    }
}
