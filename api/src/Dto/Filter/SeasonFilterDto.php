<?php

declare(strict_types=1);

namespace App\Dto\Filter;

use App\Dto\Request\PaginationRequest;
use Symfony\Component\Validator\Constraints as Assert;

class SeasonFilterDto extends PaginationRequest
{
    #[Assert\Type('integer')]
    private ?int $seasonId = null;

    public function getSeasonId(): ?int
    {
        return $this->seasonId;
    }

    public function setSeasonId(?int $seasonId): static
    {
        $this->seasonId = $seasonId;

        return $this;
    }
}
