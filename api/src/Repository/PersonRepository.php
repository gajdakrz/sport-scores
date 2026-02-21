<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Person;
use App\Entity\Sport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Person>
 */
class PersonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Person::class);
    }

    public function createActiveQueryBuilder(
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('person')
            ->andWhere('person.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('person.' . $orderBy, $direction);

        if ($sport !== null) {
            $qb->andWhere('person.sport = :sport')
                ->setParameter('sport', $sport);
        }

        return $qb;
    }

    /**
     * @param string $orderBy
     * @param string $direction
     * @return Person[]
     */
    public function findActiveSortedBy(
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null,
    ): array {

        /** @var Person[] */
        return $this->createActiveQueryBuilder($orderBy, $direction, $sport)
            ->getQuery()
            ->getResult();
    }
}
