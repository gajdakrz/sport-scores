<?php

namespace App\Repository;

use App\Entity\Competition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Competition>
 */
class CompetitionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Competition::class);
    }

    public function createIsActiveQueryBuilder(
        bool $isActive = true,
        string $orderBy = 'createdAt',
        string $direction = 'DESC'
    ): QueryBuilder {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isActive = :isActive')
            ->setParameter('isActive', $isActive)
            ->orderBy('c.' . $orderBy, $direction);
    }

    /**
     * @param bool $isActive
     * @param string $orderBy
     * @param string $direction
     * @return Competition[]
     */
    public function findIsActiveSortedBy(
        bool $isActive = true,
        string $orderBy = 'createdAt',
        string $direction = 'DESC'
    ): array {

        /** @var Competition[] */
        return $this->createIsActiveQueryBuilder($isActive, $orderBy, $direction)
            ->getQuery()
            ->getResult();
    }
}
