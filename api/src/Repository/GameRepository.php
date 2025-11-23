<?php

namespace App\Repository;

use App\Dto\GameFilterRequest;
use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    public function createActiveQueryBuilder(
        string $orderBy = 'createdAt',
        string $direction = 'DESC'
    ): QueryBuilder {
        return $this->createQueryBuilder('g')
            ->andWhere('g.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('g.' . $orderBy, $direction);
    }

    /**
     * @param string $orderBy
     * @param string $direction
     * @return Game[]
     */
    public function findActiveSortedBy(
        string $orderBy = 'createdAt',
        string $direction = 'DESC'
    ): array {

        /** @var Game[] */
        return $this->createActiveQueryBuilder($orderBy, $direction)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $orderBy
     * @param string $direction
     * @param GameFilterRequest $gameFilterRequest
     * @return Game[]
     */
    public function findActiveFilteredSortedBy(
        GameFilterRequest $gameFilterRequest,
        string $orderBy = 'createdAt',
        string $direction = 'DESC'
    ): array {
        $qb = $this->createQueryBuilder('g')
            ->join('g.event', 'event')
            ->join('event.competition', 'competition')
            ->join('competition.sport', 'sport')
            ->join('g.season', 'season')
            ->addSelect('event', 'competition', 'sport', 'season')
            ->where('g.isActive = true')
            ->orderBy('g.' . $orderBy, $direction);

        if ($gameFilterRequest->getSportId()) {
            $qb->andWhere('sport.id = :sportId')
                ->setParameter('sportId', $gameFilterRequest->getSportId());
        }

        if ($gameFilterRequest->getCompetitionId()) {
            $qb->andWhere('competition.id = :competitionId')
                ->setParameter('competitionId', $gameFilterRequest->getCompetitionId());
        }

        if ($gameFilterRequest->getEventId()) {
            $qb->andWhere('event.id = :eventId')
                ->setParameter('eventId', $gameFilterRequest->getEventId());
        }

        if ($gameFilterRequest->getSeasonId()) {
            $qb->andWhere('season.id = :seasonId')
                ->setParameter('seasonId', $gameFilterRequest->getSeasonId());
        }

        /** @var Game[] */
        return $qb->getQuery()->getResult();
    }
}
