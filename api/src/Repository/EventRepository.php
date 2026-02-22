<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\Filter\EventFilterDto;
use App\Entity\Event;
use App\Entity\Sport;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractRepository<Event>
 */
class EventRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function createActiveQueryBuilder(
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('event')
            ->join('event.competition', 'competition')
            ->join('competition.sport', 'sport')
            ->andWhere('event.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('event.' . $orderBy, $direction);

        if ($sport !== null) {
            $qb->andWhere('competition.sport = :sport')
                ->setParameter('sport', $sport);
        }

        return $qb;
    }

    /**
     * @param string $orderBy
     * @param string $direction
     * @return Event[]
     */
    public function findActiveSortedBy(
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null
    ): array {

        /** @var Event[] */
        return $this->createActiveQueryBuilder($orderBy, $direction, $sport)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param EventFilterDto $filter
     * @param string $orderBy
     * @param string $direction
     * @param ?Sport $sport
     * @return QueryBuilder
     */
    public function createActiveByFilterBuilder(
        EventFilterDto $filter,
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('event')
            ->join('event.competition', 'competition')
            ->andWhere('event.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('event.' . $orderBy, $direction);

        $this->applyFilter($qb, 'competition.sport', $sport);
        $this->applyFilter($qb, 'event.competition', $filter->getCompetitionId());
        $this->applyLikeFilter($qb, 'event.name', $filter->getName());

        return $qb;
    }
}
