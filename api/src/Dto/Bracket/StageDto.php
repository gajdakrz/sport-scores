<?php

namespace App\Dto\Bracket;

class StageDto
{
    public function __construct(
        public string $name,
        /** @var GameDto[] */
        public array $games = []
    ) {
    }

    public function addGame(GameDto $game): void
    {
        $this->games[] = $game;
    }

    /**
     * @return GameDto[]
     */
    public function getGames(): array
    {
        return $this->games;
    }
}
