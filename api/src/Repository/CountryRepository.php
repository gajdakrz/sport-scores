<?php

namespace App\Repository;

use App\Entity\Country;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Country>
 */
class CountryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Country::class);
    }

    /**
     * @param bool $isActive
     * @return Country[]
     */
    public function findIsActive(bool $isActive): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.isActive = :isActive')
            ->setParameter('isActive', $isActive)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(10)
        ;

        /** @var Country[] $result */
        $result = $qb->getQuery()->getResult() ?? [];

        return $result;
    }
}
