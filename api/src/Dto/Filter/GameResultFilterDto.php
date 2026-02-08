<?php

declare(strict_types=1);

namespace App\Dto\Filter;

use App\Dto\Request\PaginationRequest;
use App\Enum\MatchResultStatus;
use Symfony\Component\Validator\Constraints as Assert;

class GameResultFilterDto extends PaginationRequest
{
    #[Assert\DateTime(format: 'Y-m-d')]
    private ?string $date = null;

    #[Assert\Type('integer')]
    private ?int $teamId = null;

    #[Assert\Choice(callback: [MatchResultStatus::class, 'getValues'])]
    private ?string $matchResultStatus = null;

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(?string $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getTeamId(): ?int
    {
        return $this->teamId;
    }

    public function setTeamId(?int $teamId): static
    {
        $this->teamId = $teamId;

        return $this;
    }

    public function getMatchResultStatus(): ?string
    {
        return $this->matchResultStatus;
    }

    public function setMatchResultStatus(?string $matchResultStatus): static
    {
        $this->matchResultStatus = $matchResultStatus === '' ? null : $matchResultStatus;

        return $this;
    }
}
