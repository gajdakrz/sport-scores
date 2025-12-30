<?php

declare(strict_types=1);

namespace App\Model;

use App\Enum\MatchPoints;

final readonly class MatchStat
{
    private const int ADDED_VALUE = 1;

    public function __construct(
        public int $total = 0,
        public int $wins = 0,
        public int $losses = 0,
        public int $draws = 0,
        public int $unknowns = 0,
        public int $points = 0,
    ) {
    }

    public function addWin(): self
    {
        return new self(
            total: $this->total + self::ADDED_VALUE,
            wins: $this->wins + self::ADDED_VALUE,
            losses: $this->losses,
            draws: $this->draws,
            unknowns: $this->unknowns,
            points: $this->points + MatchPoints::WIN->points(),
        );
    }

    public function addLoss(): self
    {
        return new self(
            total: $this->total + self::ADDED_VALUE,
            wins: $this->wins,
            losses: $this->losses + self::ADDED_VALUE,
            draws: $this->draws,
            unknowns: $this->unknowns,
            points: $this->points + MatchPoints::LOSS->points(),
        );
    }

    public function addDraw(): self
    {
        return new self(
            total: $this->total + self::ADDED_VALUE,
            wins: $this->wins,
            losses: $this->losses,
            draws: $this->draws + self::ADDED_VALUE,
            unknowns: $this->unknowns,
            points: $this->points + MatchPoints::DRAW->points(),
        );
    }

    public function addUnknown(): self
    {
        return new self(
            total: $this->total + self::ADDED_VALUE,
            wins: $this->wins,
            losses: $this->losses,
            draws: $this->draws,
            unknowns: $this->unknowns + self::ADDED_VALUE,
            points: $this->points + MatchPoints::UNKNOWN->points(),
        );
    }

    public function winRate(): float
    {
        return $this->total === 0
            ? 0.0
            : round(($this->wins / $this->total) * 100, 2);
    }
}
