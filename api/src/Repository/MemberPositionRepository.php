<?php

namespace App\Repository;

use App\Entity\MemberPosition;
use App\Entity\Sport;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractRepository<MemberPosition>
 */
class MemberPositionRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MemberPosition::class);
    }

    public function createActiveQueryBuilder(
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('memberPosition')
            ->andWhere('memberPosition.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('memberPosition.' . $orderBy, $direction);

        if ($sport !== null) {
            $qb->andWhere('memberPosition.sport = :sport')
                ->setParameter('sport', $sport);
        }

        return $qb;
    }

    /**
     * @param string $orderBy
     * @param string $direction
     * @return MemberPosition[]
     */
    public function findActiveSortedBy(
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null,
    ): array {

        /** @var MemberPosition[] */
        return $this->createActiveQueryBuilder($orderBy, $direction, $sport)
            ->getQuery()
            ->getResult();
    }
}
