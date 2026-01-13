<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\GameFilterRequest;
use App\Entity\Game;
use App\Entity\Sport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    public function createActiveQueryBuilder(
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null
    ): QueryBuilder {

        $qb = $this->createQueryBuilder('game')
            ->join('game.event', 'event')
            ->join('event.competition', 'competition')
            ->andWhere('game.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('game.' . $orderBy, $direction);

        if ($sport !== null) {
            $qb->andWhere('competition.sport = :sport')
                ->setParameter('sport', $sport);
        }

        return $qb;
    }

    /**
     * @param string $orderBy
     * @param string $direction
     * @return Game[]
     */
    public function findActiveSortedBy(
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null
    ): array {

        /** @var Game[] */
        return $this->createActiveQueryBuilder($orderBy, $direction, $sport)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param GameFilterRequest $filter
     * @param string $orderBy
     * @param string $direction
     * @param ?Sport $sport
     * @return QueryBuilder
     */
    public function createActiveByFilterBuilder(
        GameFilterRequest $filter,
        string $orderBy = 'date',
        string $direction = 'DESC',
        ?Sport $sport = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('game')
            ->join('game.event', 'event')
            ->join('event.competition', 'competition')
            ->join('game.season', 'season')
            ->addSelect('event', 'competition', 'season')
            ->where('game.isActive = true')
            ->orderBy('game.' . $orderBy, $direction);

        if ($sport) {
            $qb->andWhere('competition.sport = :sport')
                ->setParameter('sport', $sport);
        }

        if ($filter->getCompetitionId()) {
            $qb->andWhere('competition.id = :competitionId')
                ->setParameter('competitionId', $filter->getCompetitionId());
        }

        if ($filter->getEventId()) {
            $qb->andWhere('event.id = :eventId')
                ->setParameter('eventId', $filter->getEventId());
        }

        if ($filter->getSeasonId()) {
            $qb->andWhere('season.id = :seasonId')
                ->setParameter('seasonId', $filter->getSeasonId());
        }

        if ($filter->getDate()) {
            $qb->andWhere('game.date = :date')
                ->setParameter('date', $filter->getDate());
        }

        $qb->setFirstResult($filter->getOffset())->setMaxResults($filter->getLimit());

        return $qb;
    }
}
