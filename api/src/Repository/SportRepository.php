<?php

namespace App\Repository;

use App\Entity\Sport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sport>
 */
class SportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sport::class);
    }

    public function createActiveQueryBuilder(
        string $orderBy = 'createdAt',
        string $direction = 'DESC'
    ): QueryBuilder {
        return $this->createQueryBuilder('sport')
            ->where('sport.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('sport.' . $orderBy, $direction);
    }

    /**
     * @param string $orderBy
     * @param string $direction
     * @return Sport[]
     */
    public function findActiveSortedBy(
        string $orderBy = 'createdAt',
        string $direction = 'DESC'
    ): array {

        /** @var Sport[] */
        return $this->createActiveQueryBuilder($orderBy, $direction)
            ->getQuery()
            ->getResult();
    }
}
