<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserCheckerTest extends TestCase
{
    private UserChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new UserChecker();
    }

    #[Test]
    #[TestDox('Account status is not checked before the password is verified')]
    public function deactivatedAccountPassesPreAuthentication(): void
    {
        // No exception = the test passes
        $this->checker->checkPreAuth($this->makeUser(isActive: false));
        $this->addToAssertionCount(1);
    }

    #[Test]
    #[TestDox('An active account passes the check after the password is verified')]
    public function activeAccountPassesPostAuthentication(): void
    {
        $this->checker->checkPostAuth($this->makeUser(isActive: true));
        $this->addToAssertionCount(1);
    }

    #[Test]
    #[TestDox('A deactivated account is rejected after the password is verified')]
    public function deactivatedAccountIsRejectedAfterPasswordCheck(): void
    {
        $this->expectException(CustomUserMessageAccountStatusException::class);

        $this->checker->checkPostAuth($this->makeUser(isActive: false));
    }

    #[Test]
    #[TestDox('A user from another provider is not checked')]
    public function foreignUserIsNotChecked(): void
    {
        $this->checker->checkPostAuth($this->createStub(UserInterface::class));
        $this->addToAssertionCount(1);
    }

    // --- Helpers ---

    private function makeUser(bool $isActive): User
    {
        return (new User())->setIsActive($isActive);
    }
}
