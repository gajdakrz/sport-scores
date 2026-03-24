<?php

declare(strict_types=1);

namespace App\Dto\Bracket;

use DateTimeImmutable;

class GameDto
{
    public function __construct(
        public int $id,
        /** @var TeamResultDto[] */
        public array $teams,
        public DateTimeImmutable $date
    ) {
    }

    /**
     * @return TeamResultDto[]
     */
    public function getTeams(): array
    {
        return $this->teams;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDate(): string
    {
        return $this->date->format('Y-m-d');
    }
}
