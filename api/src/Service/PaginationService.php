<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\PaginationRequest;
use App\Dto\PaginationData;
use Countable;

class PaginationService
{
    public function calculatePages(PaginationRequest $paginationRequest, int $totalItems): int
    {
        return (int) ceil($totalItems / $paginationRequest->getLimit());
    }

    public function getPaginationData(PaginationRequest $paginationRequest, Countable $paginator): PaginationData
    {
        $totalItems = count($paginator);
        $totalPages = $this->calculatePages($paginationRequest, $totalItems);

        return new PaginationData(
            currentPage: $paginationRequest->getPage(),
            limit: $paginationRequest->getLimit(),
            offset: $paginationRequest->getOffset(),
            totalItems: $totalItems,
            totalPages: $totalPages,
            hasNext: $paginationRequest->getPage() < $totalPages,
            hasPrevious: $paginationRequest->getPage() > 1,
        );
    }
}
