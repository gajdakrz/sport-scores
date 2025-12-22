<?php

declare(strict_types=1);

namespace App\Dto;

final class PaginationData
{
    public function __construct(
        public int $currentPage,
        public int $limit,
        public int $offset,
        public int $totalItems,
        public int $totalPages,
        public bool $hasNext,
        public bool $hasPrevious,
    ) {
    }
}
