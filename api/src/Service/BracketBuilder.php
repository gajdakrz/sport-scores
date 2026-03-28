<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Bracket\BracketDto;
use App\Dto\Bracket\StageDto;
use App\Dto\Bracket\GameDto;
use App\Dto\Bracket\TeamResultDto;
use App\Entity\Competition;
use App\Entity\Event;
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

            $games = $this->buildStageGames($event, $season);
            foreach ($games as $gameDto) {
                $stageDto->addGame($gameDto);
            }

            $bracket->addStage($stageDto);
        }

        return $bracket;
    }

    /**
     * @return GameDto[]
     */
    private function buildStageGames(Event $event, Season $season): array
    {
        /** @var Game[] $games */
        $games = $event->getGames()->toArray();

        // Sortowanie po dacie DESC, potem po ID zespołów ASC
        usort($games, function (Game $a, Game $b) {
            // Porównanie dat
            $dateComparison = $b->getDate() <=> $a->getDate(); // DESC
            if ($dateComparison !== 0) {
                return $dateComparison;
            }

            // Pobranie ID zespołów
            $teamIdsA = array_map(fn($gr) => $gr->getTeam()?->getId() ?? 0, $a->getGameResults()->toArray());
            $teamIdsB = array_map(fn($gr) => $gr->getTeam()?->getId() ?? 0, $b->getGameResults()->toArray());

            sort($teamIdsA);
            sort($teamIdsB);

            return $teamIdsA <=> $teamIdsB;
        });

        $stageGames = [];

        foreach ($games as $game) {
            if ($game->getSeason() !== $season || $game->getId() === null) {
                continue;
            }

            $teams = $this->buildTeamResults($game);

            if (empty($teams)) {
                continue;
            }

            $stageGames[] = new GameDto($game->getId(), $teams, $game->getDate());
        }

        return $stageGames;
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
