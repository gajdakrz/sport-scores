<?php

declare(strict_types=1);

namespace App\Dto\Bracket;

class TeamResultDto
{
    public function __construct(
        public string $teamName,
        public int $score,
        public bool $isWinner
    ) {
    }

    public function getTeamName(): string
    {
        return $this->teamName;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function isWinner(): bool
    {
        return $this->isWinner;
    }
}
