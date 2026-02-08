<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

class RegistrationUserRequest
{
    #[Assert\NotBlank(message: "Insert email.")]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank(message: "Insert password.")]
    #[Assert\Length(
        min: 6,
        max: 4096,
        minMessage: "Password must have min 6 characters.",
        maxMessage: "Password must have max 4096 characters."
    )]
    public string $plainPassword;

    #[Assert\Choice(['ROLE_USER', 'ROLE_ADMIN'])]
    public string $role = 'ROLE_USER';

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPlainPassword(): string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }
}
