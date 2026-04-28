<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\Filter\PersonFilterDto;
use App\Entity\Person;
use App\Entity\Sport;
use App\Enum\TeamFilter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractRepository<Person>
 */
class PersonRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Person::class);
    }

    public function createActiveQueryBuilder(
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null,
        ?int $currentTeamId = null,
        TeamFilter $teamFilter = TeamFilter::ALL,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('person')
            ->andWhere('person.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('person.' . $orderBy, $direction);

        if ($sport !== null) {
            $qb->andWhere('person.sport = :sport')
                ->setParameter('sport', $sport);
        }

        if ($currentTeamId !== null && $teamFilter !== TeamFilter::ALL) {
            switch ($teamFilter) {
                case TeamFilter::INCLUDED:
                    $qb->andWhere('person.currentTeam = :currentTeam')
                        ->setParameter('currentTeam', $currentTeamId);
                    break;
                case TeamFilter::EXCLUDED:
                    $qb->andWhere('person.currentTeam != :currentTeam')
                        ->setParameter('currentTeam', $currentTeamId);
                    break;
                default:
                    break;
            }
        }

        return $qb;
    }

    /**
     * @param string $orderBy
     * @param string $direction
     * @param ?Sport $sport
     * @param ?int $currentTeamId
     * @param TeamFilter $teamFilter
     * @return Person[]
     */
    public function findActiveSortedBy(
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null,
        ?int $currentTeamId = null,
        TeamFilter $teamFilter = TeamFilter::ALL,
    ): array {

        /** @var Person[] */
        return $this->createActiveQueryBuilder($orderBy, $direction, $sport, $currentTeamId, $teamFilter)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param PersonFilterDto $filter
     * @param string $orderBy
     * @param string $direction
     * @param ?Sport $sport
     * @return QueryBuilder
     */
    public function createActiveByFilterBuilder(
        PersonFilterDto $filter,
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('person')
            ->andWhere('person.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('person.' . $orderBy, $direction);

        $this->applyFilter($qb, 'person.sport', $sport);
        $this->applyFilter($qb, 'person.birthDate', $filter->getBirthDate());
        $this->applyFilter($qb, 'person.originCountry', $filter->getOriginCountryId());
        $this->applyLikeFilter($qb, 'person.firstName', $filter->getFirstName());
        $this->applyLikeFilter($qb, 'person.lastName', $filter->getLastName());

        return $qb;
    }
}
