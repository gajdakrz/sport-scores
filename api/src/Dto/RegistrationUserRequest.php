<?php

declare(strict_types=1);

namespace App\Dto;

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
}
