<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use SortDirection;

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

    /**
     * Defense-in-depth guard against DQL injection via a caller-supplied ORDER BY field:
     * only fields actually mapped on this repository's entity are allowed through.
     */
    protected function assertValidSortField(string $field): string
    {
        if (!$this->getClassMetadata()->hasField($field)) {
            throw new InvalidArgumentException(sprintf('Invalid sort field "%s".', $field));
        }

        return $field;
    }

    protected function assertValidSortDirection(string $direction): SortDirection
    {
        return match (strtoupper($direction)) {
            'ASC' => SortDirection::Ascending,
            'DESC' => SortDirection::Descending,
            default => throw new InvalidArgumentException(sprintf('Invalid sort direction "%s".', $direction)),
        };
    }

    /**
     * For ORDER BY expressions that already include a DQL alias (joined queries), the
     * owning entity's metadata can't validate them, so callers pass an explicit whitelist.
     *
     * @param string[] $allowed
     */
    protected function assertAllowedSortExpression(string $expression, array $allowed): string
    {
        if (!in_array($expression, $allowed, true)) {
            throw new InvalidArgumentException(sprintf('Invalid sort expression "%s".', $expression));
        }

        return $expression;
    }
}
