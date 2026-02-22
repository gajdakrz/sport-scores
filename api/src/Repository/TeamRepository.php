<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\Filter\TeamFilterDto;
use App\Entity\Sport;
use App\Entity\Team;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractRepository<Team>
 */
class TeamRepository extends AbstractRepository
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
     * @param TeamFilterDto $filter
     * @param string $orderBy
     * @param string $direction
     * @param ?Sport $sport
     * @return QueryBuilder
     */
    public function createActiveByFilterBuilder(
        TeamFilterDto $filter,
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('team')
            ->andWhere('team.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('team.' . $orderBy, $direction);

        $this->applyFilter($qb, 'team.sport', $sport);
        $this->applyFilter($qb, 'team.country', $filter->getCountryId());
        $this->applyFilter($qb, 'team.teamType', $filter->getTeamType());
        $this->applyLikeFilter($qb, 'team.name', $filter->getName());

        return $qb;
    }
}
