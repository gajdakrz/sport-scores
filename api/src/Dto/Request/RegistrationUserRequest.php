<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enum\Role;
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

    #[Assert\NotNull]
    #[Assert\Type(Role::class)]
    public Role $role = Role::USER;

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

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(Role $role): static
    {
        $this->role = $role;

        return $this;
    }
}
