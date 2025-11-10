<?php

namespace App\Repository;

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

    public function createIsActiveQueryBuilder(
        bool $isActive = true,
        string $orderBy = 'createdAt',
        string $direction = 'DESC'
    ): QueryBuilder {
        return $this->createQueryBuilder('g')
            ->andWhere('g.isActive = :isActive')
            ->setParameter('isActive', $isActive)
            ->orderBy('g.' . $orderBy, $direction);
    }

    /**
     * @param bool $isActive
     * @param string $orderBy
     * @param string $direction
     * @return Game[]
     */
    public function findIsActiveSortedBy(
        bool $isActive = true,
        string $orderBy = 'createdAt',
        string $direction = 'DESC'
    ): array {

        /** @var Game[] */
        return $this->createIsActiveQueryBuilder($isActive, $orderBy, $direction)
            ->getQuery()
            ->getResult();
    }
}
