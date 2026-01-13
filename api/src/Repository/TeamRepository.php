<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\TeamFilterRequest;
use App\Entity\Sport;
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

    public function createActiveQueryBuilder(
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('team')
            ->andWhere('team.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('team.' . $orderBy, $direction);

        if ($sport !== null) {
            $qb->andWhere('team.sport = :sport')
                ->setParameter('sport', $sport);
        }

        return $qb;
    }

    /**
     * @param string $orderBy
     * @param string $direction
     * @return Team[]
     */
    public function findActiveSortedBy(
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null,
    ): array {

        /** @var Team[] */
        return $this->createActiveQueryBuilder($orderBy, $direction, $sport)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param TeamFilterRequest $filter
     * @param string $orderBy
     * @param string $direction
     * @param ?Sport $sport
     * @return QueryBuilder
     */
    public function createActiveByFilterBuilder(
        TeamFilterRequest $filter,
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('team')
            ->andWhere('team.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('team.' . $orderBy, $direction);

        if ($sport !== null) {
            $qb->andWhere('team.sport = :sport')
                ->setParameter('sport', $sport);
        }

        if ($filter->getName()) {
            $qb->andWhere($qb->expr()->like('LOWER(team.name)', 'LOWER(:name)'))
                ->setParameter('name', '%' . $filter->getName() . '%');
        }

        if ($filter->getCountryId()) {
            $qb->andWhere('team.country = :countryId')
                ->setParameter('countryId', $filter->getCountryId());
        }

        if ($filter->getTeamType()) {
            $qb->andWhere('team.teamType = :teamType')
                ->setParameter('teamType', $filter->getTeamType());
        }

        $qb->setFirstResult($filter->getOffset())->setMaxResults($filter->getLimit());

        return $qb;
    }
}
