<?php

declare(strict_types=1);

namespace App\Dto\Filter;

use App\Dto\Request\PaginationRequest;
use Symfony\Component\Validator\Constraints as Assert;

class PersonFilterDto extends PaginationRequest
{
    #[Assert\Type('string')]
    private ?string $firstName = null;

    #[Assert\Type('string')]
    private ?string $lastName = null;

    #[Assert\DateTime(format: 'Y-m-d')]
    private ?string $birthDate = null;

    #[Assert\Type('integer')]
    private ?int $originCountryId = null;

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

    public function getOriginCountryId(): ?int
    {
        return $this->originCountryId;
    }

    public function setOriginCountryId(?int $originCountryId): static
    {
        $this->originCountryId = $originCountryId;

        return $this;
    }

    public function getBirthDate(): ?string
    {
        return $this->birthDate;
    }

    public function setBirthDate(?string $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }
}
