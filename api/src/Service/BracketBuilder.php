<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Bracket\BracketDto;
use App\Dto\Bracket\StageDto;
use App\Dto\Bracket\GameDto;
use App\Dto\Bracket\TeamResultDto;
use App\Entity\Competition;
use App\Entity\Game;
use App\Entity\Season;

class BracketBuilder
{
    public function build(Competition $competition, Season $season): BracketDto
    {
        $bracket = new BracketDto();

        $events = $competition->getEvents()->toArray();

        usort($events, fn($a, $b) =>
            $a->getOrderIndex() <=> $b->getOrderIndex());

        foreach ($events as $event) {
            $eventName = $event->getName();

            if ($eventName === null) {
                continue;
            }

            $stageDto = new StageDto($eventName);

            foreach ($event->getGames() as $game) {
                $gameId = $game->getId();

                if ($game->getSeason() !== $season || $gameId === null) {
                    continue;
                }

                $teams = $this->buildTeamResults($game);

                if ($teams === []) {
                    continue;
                }

                $stageDto->addGame(
                    new GameDto($gameId, $teams)
                );
            }

            $bracket->addStage($stageDto);
        }

        return $bracket;
    }

    /**
     * @return TeamResultDto[]
     */
    private function buildTeamResults(Game $game): array
    {
        $teams = [];

        foreach ($game->getGameResults() as $gameResult) {
            $teamName = $gameResult->getTeam()?->getName();
            $matchScore = $gameResult->getMatchScore();

            if ($teamName === null || $matchScore === null) {
                continue;
            }

            $teams[] = new TeamResultDto(
                $teamName,
                $matchScore,
                $gameResult->isWin()
            );
        }

        return $teams;
    }
}
