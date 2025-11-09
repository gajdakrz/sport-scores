<?php

namespace App\Repository;

use App\Entity\Team;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    /**
     * @param bool $isActive
     * @return Team[]
     */
    public function findIsActive(bool $isActive): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.isActive = :isActive')
            ->setParameter('isActive', $isActive)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults(10)
        ;

        /** @var Team[] $result */
        $result = $qb->getQuery()->getResult() ?? [];

        return $result;
    }
}
