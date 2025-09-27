<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RegistrationUserRequest
{
    #[Assert\NotBlank(message: "Podaj email.")]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank(message: "Podaj hasło")]
    #[Assert\Length(
        min: 6,
        minMessage: "Hasło musi mieć co najmniej 6 znaków.",
        max: 4096,
        maxMessage: "Hasło musi mieć co najwyżej 4096 znaków."
    )]
    public string $plainPassword;
}
