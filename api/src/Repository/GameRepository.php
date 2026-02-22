<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\Filter\GameFilterDto;
use App\Entity\Game;
use App\Entity\Sport;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractRepository<Game>
 */
class GameRepository extends AbstractRepository
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
     * @param GameFilterDto $filter
     * @param string $orderBy
     * @param string $direction
     * @param ?Sport $sport
     * @return QueryBuilder
     */
    public function createActiveByFilterBuilder(
        GameFilterDto $filter,
        string $orderBy = 'date',
        string $direction = 'DESC',
        ?Sport $sport = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('game')
            ->join('game.event', 'event')
            ->join('event.competition', 'competition')
            ->where('game.isActive = true')
            ->orderBy('game.' . $orderBy, $direction);

        $this->applyFilter($qb, 'competition.sport', $sport);
        $this->applyFilter($qb, 'game.date', $filter->getDate());
        $this->applyFilter($qb, 'game.event', $filter->getEventId());
        $this->applyFilter($qb, 'event.competition', $filter->getCompetitionId());
        $this->applyFilter($qb, 'competition.gender', $filter->getGender()?->value);

        if ($filter->getSeasonId() !== null) {
            $qb->join('game.season', 'season');
            $this->applyFilter($qb, 'game.season', $filter->getSeasonId());
        }

        return $qb;
    }
}
