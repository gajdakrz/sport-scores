<?php

namespace App\Repository;

use App\Entity\Country;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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
     * @return Country[]
     */
    public function findIsActiveSortedBy(
        bool $isActive = true,
        string $orderBy = 'createdAt',
        string $direction = 'DESC'
    ): array {

        /** @var Country[] */
        return $this->createIsActiveQueryBuilder($isActive, $orderBy, $direction)
            ->getQuery()
            ->getResult();
    }
}
