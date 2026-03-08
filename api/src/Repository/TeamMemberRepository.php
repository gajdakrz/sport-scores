<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\Filter\TeamMemberFilterDto;
use App\Entity\Sport;
use App\Entity\TeamMember;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractRepository<TeamMember>
 */
class TeamMemberRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamMember::class);
    }

    /**
     * @param TeamMemberFilterDto $filter
     * @param string $orderBy
     * @param string $direction
     * @param ?Sport $sport
     * @return QueryBuilder
     */
    public function createActiveByFilterBuilder(
        TeamMemberFilterDto $filter,
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('teamMember')
            ->join('teamMember.team', 'team')
            ->join('teamMember.person', 'person')
            ->andWhere('teamMember.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('teamMember.' . $orderBy, $direction);

        $this->applyFilter($qb, 'team.sport', $sport);
        $this->applyFilter($qb, 'teamMember.team', $filter->getTeamId());
        $this->applyFilter($qb, 'teamMember.startSeason', $filter->getStartSeasonId());
        $this->applyFilter($qb, 'teamMember.isCurrentMember', $filter->getIsCurrentMember());
        $this->applyLikeFilter($qb, 'person.firstName', $filter->getFirstName());
        $this->applyLikeFilter($qb, 'person.lastName', $filter->getLastName());

        return $qb;
    }
}
