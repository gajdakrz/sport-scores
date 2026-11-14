<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Entity\Competition;
use App\Entity\Sport;
use App\Entity\Team;
use App\Entity\UserPreference;
use App\Validator\FavouritesInSport;

#[FavouritesInSport]
class PreferenceRequest
{
    private ?Sport $favouriteSport = null;

    private ?Competition $favouriteCompetition = null;

    private ?Team $favouriteTeam = null;

    private bool $currentSeasonByDefault = true;

    public static function fromPreference(UserPreference $preference): self
    {
        return (new self())
            ->setFavouriteSport($preference->getFavouriteSport())
            ->setFavouriteCompetition($preference->getFavouriteCompetition())
            ->setFavouriteTeam($preference->getFavouriteTeam())
            ->setCurrentSeasonByDefault($preference->isCurrentSeasonByDefault());
    }

    public function getFavouriteSport(): ?Sport
    {
        return $this->favouriteSport;
    }

    public function setFavouriteSport(?Sport $favouriteSport): static
    {
        $this->favouriteSport = $favouriteSport;

        return $this;
    }

    public function getFavouriteCompetition(): ?Competition
    {
        return $this->favouriteCompetition;
    }

    public function setFavouriteCompetition(?Competition $favouriteCompetition): static
    {
        $this->favouriteCompetition = $favouriteCompetition;

        return $this;
    }

    public function getFavouriteTeam(): ?Team
    {
        return $this->favouriteTeam;
    }

    public function setFavouriteTeam(?Team $favouriteTeam): static
    {
        $this->favouriteTeam = $favouriteTeam;

        return $this;
    }

    public function isCurrentSeasonByDefault(): bool
    {
        return $this->currentSeasonByDefault;
    }

    public function setCurrentSeasonByDefault(bool $currentSeasonByDefault): static
    {
        $this->currentSeasonByDefault = $currentSeasonByDefault;

        return $this;
    }
}
