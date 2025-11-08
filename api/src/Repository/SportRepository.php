<?php

namespace App\Repository;

use App\Entity\Sport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    /**
     * @param bool $isActive
     * @return Sport[]
     */
    public function findIsActive(bool $isActive): array
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.isActive = :isActive')
            ->setParameter('isActive', $isActive)
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(10)
        ;

        /** @var Sport[] $result */
        $result = $qb->getQuery()->getResult() ?? [];

        return $result;
    }
}
