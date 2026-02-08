<?php

declare(strict_types=1);

namespace App\Dto\Filter;

use App\Dto\Request\PaginationRequest;
use Symfony\Component\Validator\Constraints as Assert;

class TeamGameResultFilterDto extends PaginationRequest
{
    #[Assert\Type('integer')]
    private ?int $seasonId = null;

    #[Assert\Type('integer')]
    private ?int $competitionId = null;

    public function getSeasonId(): ?int
    {
        return $this->seasonId;
    }

    public function setSeasonId(?int $seasonId): static
    {
        $this->seasonId = $seasonId;

        return $this;
    }

    public function getCompetitionId(): ?int
    {
        return $this->competitionId;
    }

    public function setCompetitionId(?int $competitionId): static
    {
        $this->competitionId = $competitionId;

        return $this;
    }
}
