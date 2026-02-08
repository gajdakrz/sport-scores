<?php

declare(strict_types=1);

namespace App\Dto\Filter;

use App\Dto\Request\PaginationRequest;
use Symfony\Component\Validator\Constraints as Assert;

class GameFilterDto extends PaginationRequest
{
    #[Assert\Type('integer')]
    private ?int $competitionId = null;

    #[Assert\Type('integer')]
    private ?int $eventId = null;

    #[Assert\Type('integer')]
    private ?int $seasonId = null;

    #[Assert\DateTime(format: 'Y-m-d')]
    private ?string $date = null;

    public function getCompetitionId(): ?int
    {
        return $this->competitionId;
    }

    public function setCompetitionId(?int $competitionId): static
    {
        $this->competitionId = $competitionId;

        return $this;
    }

    public function getEventId(): ?int
    {
        return $this->eventId;
    }

    public function setEventId(?int $eventId): static
    {
        $this->eventId = $eventId;

        return $this;
    }

    public function getSeasonId(): ?int
    {
        return $this->seasonId;
    }

    public function setSeasonId(?int $seasonId): static
    {
        $this->seasonId = $seasonId;

        return $this;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(?string $date): static
    {
        $this->date = $date;

        return $this;
    }
}
