<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    public function createIsActiveQueryBuilder(
        bool $isActive = true,
        string $orderBy = 'createdAt',
        string $direction = 'DESC'
    ): QueryBuilder {
        return $this->createQueryBuilder('e')
            ->andWhere('e.isActive = :isActive')
            ->setParameter('isActive', $isActive)
            ->orderBy('e.' . $orderBy, $direction);
    }

    /**
     * @param bool $isActive
     * @param string $orderBy
     * @param string $direction
     * @return Event[]
     */
    public function findIsActiveSortedBy(
        bool $isActive = true,
        string $orderBy = 'createdAt',
        string $direction = 'DESC'
    ): array {

        /** @var Event[] */
        return $this->createIsActiveQueryBuilder($isActive, $orderBy, $direction)
            ->getQuery()
            ->getResult();
    }
}
