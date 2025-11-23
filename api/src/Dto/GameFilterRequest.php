<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class GameFilterRequest
{
    #[Assert\Type('integer')]
    private ?int $sportId = null;

    #[Assert\Type('integer')]
    private ?int $competitionId = null;

    #[Assert\Type('integer')]
    private ?int $eventId = null;

    #[Assert\Type('integer')]
    private ?int $seasonId = null;

    public function getSportId(): ?int
    {
        return $this->sportId;
    }

    public function setSportId(?int $sportId): static
    {
        $this->sportId = $sportId;

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
}
