<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

abstract class PaginationRequest
{
    #[Assert\Type('integer')]
    #[Assert\Positive]
    protected int $page = 1;

    #[Assert\Type('integer')]
    #[Assert\Positive]
    protected int $limit = 10;

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): static
    {
        $this->page = max(1, $page);

        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): static
    {
        $this->limit = min(max(1, $limit), 100);

        return $this;
    }

    public function getOffset(): int
    {
        return ($this->getPage() - 1) * $this->getLimit();
    }
}
