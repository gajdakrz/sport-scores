<?php

namespace App\Repository;

use App\Dto\GameResultFilterRequest;
use App\Dto\TeamDetailFilterRequest;
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

    public function buildActiveByTeamAndSeason(
        Team $team,
        ?Season $season = null,
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

        return $qb;
    }

    /**
     * @param Team $team
     * @param ?Season $season
     * @param string $orderBy
     * @param string $direction
     * @return GameResult[]
     */
    public function findActiveByTeamAndSeason(
        Team $team,
        ?Season $season = null,
        string $orderBy = 'gr1.createdAt',
        string $direction = 'DESC'
    ): array {
        $qb = $this->buildActiveByTeamAndSeason($team, $season, $orderBy, $direction);

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
        string $orderBy = 'gr1.createdAt',
        string $direction = 'DESC'
    ): Paginator {
        $qb = $this->buildActiveByTeamAndSeason($team, $season, $orderBy, $direction);
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
     *     wins: GameResult[],
     *     losses: GameResult[],
     *     draws: GameResult[],
     *     unknowns: GameResult[],
     *     all: GameResult[],
     *     stats: array{
     *         total: int,
     *         wins: int,
     *         losses: int,
     *         draws: int,
     *         unknowns: int,
     *         winRate: float,
     *         points: int
     *     }
     * }>
     */
    public function getResultsGroupedBySeason(Team $team): array
    {
        $results = $this->findActiveByTeamAndSeason($team);
        $groupedBySeason = [];

        if ($results === []) {
            return $groupedBySeason;
        }

        foreach ($results as $result) {
            $season = $result->getGame()?->getSeason();
            $seasonId = $season?->getId();
            if ($seasonId === null) {
                continue;
            }
            $competition = $result->getGame()?->getEvent()?->getCompetition();
            $team = $result->getTeam();

            if (!isset($groupedBySeason[$seasonId])) {
                $groupedBySeason[$seasonId] = [
                    'season' => $season,
                    'competition' => $competition,
                    'team' => $team,
                    'wins' => [],
                    'losses' => [],
                    'draws' => [],
                    'unknowns' => [],
                    'all' => [],
                    'stats' => [
                        'total' => 0,
                        'wins' => 0,
                        'losses' => 0,
                        'draws' => 0,
                        'unknowns' => 0,
                        'winRate' => 0,
                        'points' => 0,
                    ],
                ];
            }
            $matchResult = $result->getMatchResult();
            $groupedBySeason[$seasonId]['all'][] = $result;
            $groupedBySeason[$seasonId]['stats']['total']++;

            match ($matchResult) {
                MatchResultStatus::WIN => [
                    $groupedBySeason[$seasonId]['wins'][] = $result,
                    $groupedBySeason[$seasonId]['stats']['wins']++,
                    $groupedBySeason[$seasonId]['stats']['points'] += 3,
                ],
                MatchResultStatus::LOSS => [
                    $groupedBySeason[$seasonId]['losses'][] = $result,
                    $groupedBySeason[$seasonId]['stats']['losses']++,
                ],
                MatchResultStatus::DRAW => [
                    $groupedBySeason[$seasonId]['draws'][] = $result,
                    $groupedBySeason[$seasonId]['stats']['draws']++,
                    $groupedBySeason[$seasonId]['stats']['points']++,
                ],
                MatchResultStatus::UNKNOWN => [
                    $groupedBySeason[$seasonId]['unknowns'][] = $result,
                    $groupedBySeason[$seasonId]['stats']['unknowns']++,
                ],
            };
            $total = $groupedBySeason[$seasonId]['stats']['total'];
            $wins = $groupedBySeason[$seasonId]['stats']['wins'];
            $groupedBySeason[$seasonId]['stats']['winRate'] = round(($wins / $total) * 100, 2);
        }
        krsort($groupedBySeason);

        return $groupedBySeason;
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
