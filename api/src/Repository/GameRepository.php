<?php

namespace App\Repository;

use App\Dto\GameFilterRequest;
use App\Entity\Game;
use App\Entity\Sport;
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
        string $direction = 'DESC',
        ?Sport $sport = null
    ): QueryBuilder {

        $qb = $this->createQueryBuilder('game')
            ->join('game.event', 'event')
            ->join('event.competition', 'competition')
            ->andWhere('game.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('game.' . $orderBy, $direction);

        if ($sport !== null) {
            $qb->andWhere('competition.sport = :sport')
                ->setParameter('sport', $sport);
        }

        return $qb;
    }

    /**
     * @param string $orderBy
     * @param string $direction
     * @return Game[]
     */
    public function findActiveSortedBy(
        string $orderBy = 'createdAt',
        string $direction = 'DESC',
        ?Sport $sport = null
    ): array {

        /** @var Game[] */
        return $this->createActiveQueryBuilder($orderBy, $direction, $sport)
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
        string $direction = 'DESC',
        ?Sport $sport = null,
    ): array {
        $qb = $this->createQueryBuilder('g')
            ->join('g.event', 'event')
            ->join('event.competition', 'competition')
            ->join('competition.sport', 'sport')
            ->join('g.season', 'season')
            ->addSelect('event', 'competition', 'sport', 'season')
            ->where('g.isActive = true')
            ->orderBy('g.' . $orderBy, $direction);

        if ($sport !== null) {
            $qb->andWhere('competition.sport = :sport')
                ->setParameter('sport', $sport);
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
