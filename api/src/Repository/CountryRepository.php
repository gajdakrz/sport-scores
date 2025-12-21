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

    public function createActiveQueryBuilder(
        string $orderBy = 'createdAt',
        string $direction = 'DESC'
    ): QueryBuilder {
        return $this->createQueryBuilder('country')
            ->andWhere('country.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('country.' . $orderBy, $direction);
    }

    /**
     * @param string $orderBy
     * @param string $direction
     * @return Country[]
     */
    public function findActiveSortedBy(
        string $orderBy = 'createdAt',
        string $direction = 'DESC'
    ): array {

        /** @var Country[] */
        return $this->createActiveQueryBuilder($orderBy, $direction)
            ->getQuery()
            ->getResult();
    }
}
