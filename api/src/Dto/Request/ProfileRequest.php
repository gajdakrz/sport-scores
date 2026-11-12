<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Entity\User;

class ProfileRequest
{
    use UserNameTrait;

    public static function fromUser(User $user): self
    {
        return (new self())
            ->setFirstName($user->getFirstName())
            ->setLastName($user->getLastName());
    }
}
