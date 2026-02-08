<?php

declare(strict_types=1);

namespace App\Dto\Filter;

use App\Dto\Request\PaginationRequest;
use Symfony\Component\Validator\Constraints as Assert;

class EventFilterDto extends PaginationRequest
{
    #[Assert\Type('string')]
    private ?string $name = null;

    #[Assert\Type('integer')]
    private ?int $competitionId = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

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
