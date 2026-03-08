<?php

declare(strict_types=1);

namespace App\Dto\Filter;

use App\Dto\Request\PaginationRequest;
use App\Enum\TeamType;
use Symfony\Component\Validator\Constraints as Assert;

class TeamMemberFilterDto extends PaginationRequest
{
    #[Assert\Type('string')]
    private ?string $firstName = null;

    #[Assert\Type('string')]
    private ?string $lastName = null;

    #[Assert\Type('integer')]
    private ?int $teamId = null;

    #[Assert\Type('integer')]
    private ?int $startSeasonId = null;

    #[Assert\Type('bool')]
    private ?bool $isCurrentMember = null;

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getStartSeasonId(): ?int
    {
        return $this->startSeasonId;
    }

    public function setStartSeasonId(?int $startSeasonId): static
    {
        $this->startSeasonId = $startSeasonId;

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

    public function getIsCurrentMember(): ?bool
    {
        return $this->isCurrentMember;
    }

    public function setIsCurrentMember(?bool $isCurrentMember): static
    {
        $this->isCurrentMember = $isCurrentMember;

        return $this;
    }
}
