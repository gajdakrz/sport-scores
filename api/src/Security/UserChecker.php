<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        // Required by UserCheckerInterface; the account status is checked in checkPostAuth(),
        // so it isn't revealed before the password is verified.
    }

    /**
     * Runs only after a valid password: someone who knows just the email gets "Invalid credentials"
     * for a deactivated account too, so they can't tell whether the account exists.
     */
    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Your account has been deactivated.');
        }
    }
}
