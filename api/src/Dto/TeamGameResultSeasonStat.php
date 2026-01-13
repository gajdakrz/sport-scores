<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Season;
use App\Entity\Competition;

final readonly class TeamGameResultSeasonStat
{
    public function __construct(
        public Season $season,
        public Competition $competition,
        public int $total,
        public int $wins,
        public int $losses,
        public int $draws,
        public int $unknowns,
    ) {
    }

    public function winRate(): float
    {
        return $this->total === 0
            ? 0.0
            : round(($this->wins / $this->total) * 100, 2);
    }
}
