<?php

declare(strict_types=1);

namespace App\Dto\Request\Trait;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * First and last name of a user account, shared by the registration and profile requests.
 */
trait UserNameTrait
{
    #[Assert\NotBlank(message: "Insert first name.", normalizer: 'trim')]
    #[Assert\Length(max: 100, maxMessage: "First name must have max 100 characters.")]
    public string $firstName = '';

    #[Assert\NotBlank(message: "Insert last name.", normalizer: 'trim')]
    #[Assert\Length(max: 100, maxMessage: "Last name must have max 100 characters.")]
    public string $lastName = '';

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }
}
