<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\GameResultFilterRequest;
use App\Dto\TeamDetailFilterRequest;
use App\Model\MatchStat;
use App\Entity\Competition;
use App\Entity\Game;
use App\Entity\GameResult;
use App\Entity\Season;
use App\Entity\Sport;
use App\Entity\Team;
use App\Enum\MatchResultStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
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

    public function buildActiveByTeamAndSeasonAndCompetition(
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

    /**
     * @param Team $team
     * @param ?Season $season
     * @param ?Competition $competition
     * @param string $orderBy
     * @param string $direction
     * @return GameResult[]
     */
    public function findActiveByTeamAndSeason(
        Team $team,
        ?Season $season = null,
        ?Competition $competition = null,
        string $orderBy = 'gr1.createdAt',
        string $direction = 'DESC'
    ): array {
        $qb = $this->buildActiveByTeamAndSeasonAndCompetition($team, $season, $competition, $orderBy, $direction);

        /** @var GameResult[] */
        return $qb->getQuery()->getResult();
    }

    /**
     * @return Paginator<GameResult>
     * @phpstan-return Paginator<GameResult>
     */
    public function findActiveByTeamAndSeasonPaginated(
        TeamDetailFilterRequest $filter,
        Team $team,
        ?Season $season = null,
        ?Competition $competition = null,
        string $orderBy = 'gr1.createdAt',
        string $direction = 'DESC'
    ): Paginator {
        $qb = $this->buildActiveByTeamAndSeasonAndCompetition($team, $season, $competition, $orderBy, $direction);
        $qb->setFirstResult($filter->getOffset())->setMaxResults($filter->getLimit());

        /** @var Paginator<GameResult> */
        return new Paginator($qb);
    }

    /**
     * @param Team $team
     * @return array<int, array{
     *     season: ?Season,
     *     competition: ?Competition,
     *     team: ?Team,
     *     stats: MatchStat
     * }>
     */
    public function getResultsGroupedBySeasonAndCompetition(Team $team): array
    {
        $results = $this->findActiveByTeamAndSeason($team);
        $grouped = [];

        foreach ($results as $result) {
            $game = $result->getGame();
            $season = $game?->getSeason();
            $competition = $game?->getEvent()?->getCompetition();

            if ($season === null || $competition === null) {
                continue;
            }

            $key = $season->getId() . '_' . $competition->getId();

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'season' => $season,
                    'competition' => $competition,
                    'team' => $result->getTeam(),
                    'stats' => new MatchStat(),
                ];
            }

            /** @var MatchStat $matchStat */
            $matchStat = $grouped[$key]['stats'];

            $grouped[$key]['stats'] = match ($result->getMatchResult()) {
                MatchResultStatus::WIN => $matchStat->addWin(),
                MatchResultStatus::LOSS => $matchStat->addLoss(),
                MatchResultStatus::DRAW => $matchStat->addDraw(),
                MatchResultStatus::UNKNOWN => $matchStat->addUnknown(),
            };
        }

        return array_values($grouped);
    }

    /**
     * @param GameResultFilterRequest $filter
     * @param string $orderBy
     * @param string $direction
     * @param ?Sport $sport
     * @return Paginator<GameResult>
     */
    public function findActivePaginatedByFilter(
        GameResultFilterRequest $filter,
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null,
    ): Paginator {
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

        /** @var Paginator<GameResult> */
        return new Paginator($qb);
    }
}
