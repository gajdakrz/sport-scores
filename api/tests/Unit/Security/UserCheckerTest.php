<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
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
    #[TestDox('checkPreAuth does nothing when user is not an instance of App User')]
    public function checkPreAuthDoesNothingForNonAppUser(): void
    {
        $foreignUser = $this->createMock(UserInterface::class);

        // Brak wyjątku = test przechodzi
        $this->checker->checkPreAuth($foreignUser);
        $this->addToAssertionCount(1);
    }

    #[Test]
    #[TestDox('checkPreAuth does not throw when the user is active')]
    public function checkPreAuthDoesNotThrowForActiveUser(): void
    {
        $user = $this->makeUser(isActive: true);

        $this->checker->checkPreAuth($user);
        $this->addToAssertionCount(1);
    }

    #[Test]
    #[TestDox('checkPreAuth throws CustomUserMessageAccountStatusException when user is inactive')]
    public function checkPreAuthThrowsForInactiveUser(): void
    {
        $user = $this->makeUser(isActive: false);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Twoje konto zostało dezaktywowane.');

        $this->checker->checkPreAuth($user);
    }

    #[Test]
    #[TestDox('checkPostAuth does nothing regardless of user state')]
    public function checkPostAuthDoesNothing(): void
    {
        $activeUser   = $this->makeUser(isActive: true);
        $inactiveUser = $this->makeUser(isActive: false);

        $this->checker->checkPostAuth($activeUser);
        $this->checker->checkPostAuth($inactiveUser);
        $this->addToAssertionCount(1);
    }

    // --- Helpers ---

    private function makeUser(bool $isActive): User&MockObject
    {
        $user = $this->createMock(User::class);
        $user->method('isActive')->willReturn($isActive);

        return $user;
    }
}
