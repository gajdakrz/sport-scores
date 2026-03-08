<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\Filter\GameResultFilterDto;
use App\Dto\Filter\TeamDetailFilterDto;
use App\Dto\Filter\TeamGameResultFilterDto;
use App\Entity\Competition;
use App\Entity\Game;
use App\Entity\GameResult;
use App\Entity\Season;
use App\Entity\Sport;
use App\Entity\Team;
use App\Enum\MatchResultStatus;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractRepository<GameResult>
 */
class GameResultRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameResult::class);
    }

    public function createActiveQueryBuilder(
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('gameResult')
            ->join('gameResult.game', 'game')
            ->join('game.event', 'event')
            ->join('event.competition', 'competition')
            ->join('competition.sport', 'sport')
            ->andWhere('gameResult.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('gameResult.' . $orderBy, $direction);

        if ($sport !== null) {
            $qb->andWhere('competition.sport = :sport')
                ->setParameter('sport', $sport);
        }

        return $qb;
    }

    /**
     * @param string $orderBy
     * @param string $direction
     * @return GameResult[]
     */
    public function findActiveSortedBy(
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null
    ): array {

        /** @var GameResult[] */
        return $this->createActiveQueryBuilder($orderBy, $direction, $sport)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Game $game
     * @param string $orderBy
     * @param string $direction
     * @return GameResult[]
     */
    public function findActiveByGame(
        Game $game,
        string $orderBy = 'createdAt',
        string $direction = 'DESC'
    ): array {
        /** @var GameResult[] */
        return $this->createQueryBuilder('gr')
            ->where('gr.game = :game')
            ->andWhere('gr.isActive = :isActive')
            ->setParameter('isActive', true)
            ->setParameter('game', $game)
            ->orderBy('gr.' . $orderBy, $direction)
            ->getQuery()
            ->getResult();
    }

    public function activeByTeamAndSeasonAndCompetitionBuilder(
        TeamDetailFilterDto $filter,
        Team $team,
        ?Season $season = null,
        ?Competition $competition = null,
        string $orderBy = 'gr1.createdAt',
        string $direction = 'DESC'
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('gr1')
            ->select('gr1', 'gr2', 't1', 't2', 'g', 's', 'e', 'c')
            ->join('gr1.team', 't1')
            ->join('gr1.game', 'g')
            ->join('g.gameResults', 'gr2', 'WITH', 'gr2.id != gr1.id')
            ->join('gr2.team', 't2')
            ->join('g.event', 'e')
            ->join('g.season', 's')
            ->join('e.competition', 'c')
            ->where('gr1.team = :team')
            ->andWhere('gr1.isActive = :isActive')
            ->setParameter('isActive', true)
            ->setParameter('team', $team)
            ->orderBy($orderBy, $direction);

        if ($season !== null) {
            $qb->andWhere('g.season = :season')
                ->setParameter('season', $season);
        }

        if ($competition !== null) {
            $qb->andWhere('e.competition = :competition')
                ->setParameter('competition', $competition);
        }

        return $qb;
    }

    public function groupedResultsBySeasonAndCompetitionBuilder(
        Team $team,
        TeamGameResultFilterDto $filter,
        string $orderBy = 'season.startYear',
        string $direction = 'DESC',
        ?Sport $sport = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('gameResult')
            ->select(
                'NEW App\Dto\Response\TeamGameStatResponseDto(season, competition, ' .
                'count(gameResult.id), ' .
                'SUM(CASE WHEN gameResult.matchScore > opponent.matchScore THEN 1 ELSE 0 END), ' .
                'SUM(CASE WHEN gameResult.matchScore < opponent.matchScore THEN 1 ELSE 0 END), ' .
                'SUM(CASE WHEN gameResult.matchScore = opponent.matchScore THEN 1 ELSE 0 END), ' .
                'SUM(CASE WHEN gameResult.matchScore IS NULL OR opponent.matchScore IS NULL THEN 1 ELSE 0 END))'
            )
            ->join('gameResult.team', 'team')
            ->join('gameResult.game', 'game')
            ->join('game.season', 'season')
            ->join('game.event', 'event')
            ->join('game.gameResults', 'opponent', 'WITH', 'opponent.id != gameResult.id')
            ->join('event.competition', 'competition')
            ->andWhere('gameResult.team = :team')
            ->andWhere('gameResult.isActive = true')
            ->setParameter('team', $team)
            ->groupBy('season.id, competition.id')
            ->orderBy($orderBy, $direction);

        $this->applyFilter($qb, 'competition.sport', $sport);
        $this->applyFilter($qb, 'game.season', $filter->getSeasonId());
        $this->applyFilter($qb, 'event.competition', $filter->getCompetitionId());

        return $qb;
    }

    /**
     * @param GameResultFilterDto $filter
     * @param string $orderBy
     * @param string $direction
     * @param ?Sport $sport
     * @return QueryBuilder
     */
    public function createActiveByFilterBuilder(
        GameResultFilterDto $filter,
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('gameResult')
            ->join('gameResult.game', 'game')
            ->join('game.event', 'event')
            ->join('event.competition', 'competition')
            ->join('competition.sport', 'sport')
            ->join(
                GameResult::class,
                'opponent',
                'WITH',
                'opponent.game = gameResult.game AND opponent.id != gameResult.id'
            )
            ->andWhere('gameResult.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('gameResult.' . $orderBy, $direction);

        $this->applyFilter($qb, 'competition.sport', $sport);
        $this->applyFilter($qb, 'game.date', $filter->getDate());
        $this->applyFilter($qb, 'gameResult.team', $filter->getTeamId());

        if ($filter->getMatchResultStatus()) {
            switch ($filter->getMatchResultStatus()) {
                case MatchResultStatus::WIN->value:
                    $qb->andWhere('gameResult.matchScore > opponent.matchScore');
                    break;

                case MatchResultStatus::LOSS->value:
                    $qb->andWhere('gameResult.matchScore < opponent.matchScore');
                    break;

                case MatchResultStatus::DRAW->value:
                    $qb->andWhere('gameResult.matchScore = opponent.matchScore');
                    break;
                default:
                    break;
            }
        }

        return $qb;
    }
}
