<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * @template T of object
 * @extends ServiceEntityRepository<T>
 */
abstract class AbstractRepository extends ServiceEntityRepository
{
    protected function applyFilter(QueryBuilder $qb, string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $param = lcfirst(str_replace('.', '', ucwords($field, '.')));

        $qb->andWhere("$field = :$param")
            ->setParameter($param, $value);
    }

    protected function applyLikeFilter(QueryBuilder $qb, string $field, ?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $param = lcfirst(str_replace('.', '', ucwords($field, '.')));

        $qb->andWhere($qb->expr()->like("LOWER($field)", "LOWER(:$param)"))
            ->setParameter($param, '%' . $value . '%');
    }
}
