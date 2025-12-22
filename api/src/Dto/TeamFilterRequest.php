<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\TeamType;
use Symfony\Component\Validator\Constraints as Assert;

class TeamFilterRequest extends PaginationRequest
{
    #[Assert\Type('string')]
    private ?string $name = null;

    #[Assert\Type('integer')]
    private ?int $countryId = null;

    #[Assert\Choice(callback: [TeamType::class, 'cases'])]
    private ?TeamType $teamType = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCountryId(): ?int
    {
        return $this->countryId;
    }

    public function setCountryId(?int $countryId): static
    {
        $this->countryId = $countryId;

        return $this;
    }

    public function getTeamType(): ?TeamType
    {
        return $this->teamType;
    }

    public function setTeamType(?TeamType $teamType): static
    {
        $this->teamType = $teamType;

        return $this;
    }
}
