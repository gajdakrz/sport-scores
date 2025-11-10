<?php

namespace App\Repository;

use App\Entity\Team;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Team>
 */
class TeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    public function createIsActiveQueryBuilder(
        bool $isActive = true,
        string $orderBy = 'createdAt',
        string $direction = 'DESC'
    ): QueryBuilder {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isActive = :isActive')
            ->setParameter('isActive', $isActive)
            ->orderBy('t.' . $orderBy, $direction);
    }

    /**
     * @param bool $isActive
     * @param string $orderBy
     * @param string $direction
     * @return Team[]
     */
    public function findIsActiveSortedBy(
        bool $isActive = true,
        string $orderBy = 'createdAt',
        string $direction = 'DESC'
    ): array {

        /** @var Team[] */
        return $this->createIsActiveQueryBuilder($isActive, $orderBy, $direction)
            ->getQuery()
            ->getResult();
    }
}
