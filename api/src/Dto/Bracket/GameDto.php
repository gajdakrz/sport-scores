<?php

declare(strict_types=1);

namespace App\Dto\Bracket;

class GameDto
{
    public function __construct(
        public int $id,
        /** @var TeamResultDto[] */
        public array $teams
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
}
