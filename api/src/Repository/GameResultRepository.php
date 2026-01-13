<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\GameResultFilterRequest;
use App\Dto\TeamDetailFilterRequest;
use App\Dto\TeamGameResultFilterRequest;
use App\Entity\Competition;
use App\Entity\Game;
use App\Entity\GameResult;
use App\Entity\Season;
use App\Entity\Sport;
use App\Entity\Team;
use App\Enum\MatchResultStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameResult>
 */
class GameResultRepository extends ServiceEntityRepository
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
        TeamDetailFilterRequest $filter,
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
        $qb->setFirstResult($filter->getOffset())->setMaxResults($filter->getLimit());

        return $qb;
    }

    public function groupedResultsBySeasonAndCompetitionBuilder(
        Team $team,
        TeamGameResultFilterRequest $filter,
        string $orderBy = 's.startYear',
        string $direction = 'DESC',
        ?Sport $sport = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('gr')
            ->select(
                'NEW App\Dto\TeamGameResultSeasonStat(s, c, ' .
                'count(gr.id), ' .
                'SUM(CASE WHEN gr.matchScore > opponent.matchScore THEN 1 ELSE 0 END), ' .
                'SUM(CASE WHEN gr.matchScore < opponent.matchScore THEN 1 ELSE 0 END), ' .
                'SUM(CASE WHEN gr.matchScore = opponent.matchScore THEN 1 ELSE 0 END), ' .
                'SUM(CASE WHEN gr.matchScore IS NULL OR opponent.matchScore IS NULL THEN 1 ELSE 0 END))'
            )
            ->join('gr.team', 't')
            ->join('gr.game', 'g')
            ->join('g.season', 's')
            ->join('g.event', 'e')
            ->join('e.competition', 'c')
            ->join('g.gameResults', 'opponent', 'WITH', 'opponent.id != gr.id')
            ->andWhere('gr.team = :team')
            ->andWhere('gr.isActive = true')
            ->setParameter('team', $team)
            ->groupBy('s.id, c.id')
            ->orderBy($orderBy, $direction);

        if ($filter->getSeasonId() !== null) {
            $qb->andWhere('g.season = :seasonId')
                ->setParameter('seasonId', $filter->getSeasonId());
        }

        if ($filter->getCompetitionId() !== null) {
            $qb->andWhere('e.competition = :competitionId')
                ->setParameter('competitionId', $filter->getCompetitionId());
        }

        if ($sport !== null) {
            $qb->andWhere('c.sport = :sport')
                ->setParameter('sport', $sport);
        }

        return $qb;
    }

    /**
     * @param GameResultFilterRequest $filter
     * @param string $orderBy
     * @param string $direction
     * @param ?Sport $sport
     * @return QueryBuilder
     */
    public function createActiveByFilterBuilder(
        GameResultFilterRequest $filter,
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

        if ($sport !== null) {
            $qb->andWhere('competition.sport = :sport')
                ->setParameter('sport', $sport);
        }

        if ($filter->getDate()) {
            $qb->andWhere('game.date = :date')
                ->setParameter('date', $filter->getDate());
        }

        if ($filter->getTeamId()) {
            $qb->andWhere('gameResult.team = :teamId')
                ->setParameter('teamId', $filter->getTeamId());
        }

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
        $qb->setFirstResult($filter->getOffset())->setMaxResults($filter->getLimit());

        return $qb;
    }
}
