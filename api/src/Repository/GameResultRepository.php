<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\GameResult;
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
}
