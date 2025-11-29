<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\GameResult;
use App\Entity\Team;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameResult>
 */
class GameResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameResult::class);
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
     * @return GameResult[]
     */
    public function findActiveSortedBy(
        string $orderBy = 'createdAt',
        string $direction = 'DESC'
    ): array {

        /** @var GameResult[] */
        return $this->createActiveQueryBuilder($orderBy, $direction)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Game $game
     * @param string $orderBy
     * @param string $direction
     * @return GameResult[]
     */
    public function findActiveByGame(
        Game $game,
        string $orderBy = 'createdAt',
        string $direction = 'DESC'
    ): array {
        /** @var GameResult[] */
        return $this->createQueryBuilder('gr')
            ->where('gr.game = :game')
            ->andWhere('gr.isActive = :isActive')
            ->setParameter('isActive', true)
            ->setParameter('game', $game)
            ->orderBy('gr.' . $orderBy, $direction)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Team $team
     * @param string $orderBy
     * @param string $direction
     * @return GameResult[]
     */
    public function findActiveByTeam(
        Team $team,
        string $orderBy = 'createdAt',
        string $direction = 'DESC'
    ): array {
        /** @var GameResult[] */
        return $this->createQueryBuilder('gr1')
            ->select('gr1', 'gr2', 'team1', 'team2', 'game')
            ->join('gr1.team', 'team1')
            ->join('gr1.game', 'game')
            ->join('game.gameResults', 'gr2', 'WITH', 'gr2.id != gr1.id')
            ->join('gr2.team', 'team2')
            ->join('game.event', 'event')
            ->join('game.season', 'season')
            ->join('event.competition', 'competition')
            ->where('gr1.team = :team')
            ->andWhere('gr1.isActive = :isActive')
            ->setParameter('isActive', true)
            ->setParameter('team', $team)
            ->orderBy('gr1.' . $orderBy, $direction)
            ->getQuery()
            ->getResult();
    }
}
