<?php

namespace App\Repository;

use App\Dto\EventFilterRequest;
use App\Entity\Event;
use App\Entity\Sport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
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
     * @param EventFilterRequest $filter
     * @param string $orderBy
     * @param string $direction
     * @param ?Sport $sport
     * @return Paginator<Event>
     */
    public function findActivePaginatedByFilter(
        EventFilterRequest $filter,
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null,
    ): Paginator {
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

        if ($filter->getName()) {
            $qb->andWhere($qb->expr()->like('LOWER(event.name)', 'LOWER(:name)'))
                ->setParameter('name', '%' . $filter->getName() . '%');
        }

        if ($filter->getCompetitionId()) {
            $qb->andWhere('event.competition = :competitionId')
                ->setParameter('competitionId', $filter->getCompetitionId());
        }

        $qb->setFirstResult($filter->getOffset())->setMaxResults($filter->getLimit());

        /** @var Paginator<Event> */
        return new Paginator($qb);
    }
}
