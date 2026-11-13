<?php

declare(strict_types=1);

namespace App\Dto\Filter;

use App\Dto\Request\PaginationRequest;
use App\Enum\GenderCategory;
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

    #[Assert\Choice(callback: [GenderCategory::class, 'cases'])]
    private ?GenderCategory $gender = null;

    /**
     * Only games of seasons starting in the current year. Off unless sent as 1 (the app's links to Games do).
     */
    #[Assert\Type('bool')]
    private ?bool $currentSeason = null;

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

    public function getGender(): ?GenderCategory
    {
        return $this->gender;
    }

    public function setGender(?GenderCategory $gender): static
    {
        $this->gender = $gender;
        return $this;
    }

    public function getCurrentSeason(): ?bool
    {
        return $this->currentSeason;
    }

    public function setCurrentSeason(?bool $currentSeason): static
    {
        $this->currentSeason = $currentSeason;

        return $this;
    }

    /**
     * A season picked explicitly takes precedence over the "current season" filter.
     */
    public function appliesCurrentSeason(): bool
    {
        return $this->seasonId === null && $this->currentSeason === true;
    }
}
