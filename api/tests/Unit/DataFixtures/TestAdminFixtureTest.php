<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataFixtures;

use App\DataFixtures\TestAdminFixture;
use App\Entity\User;
use App\Enum\Role;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class TestAdminFixtureTest extends TestCase
{
    #[Test]
    #[TestDox('Persists a user with ROLE_ADMIN and a hashed password')]
    public function loadPersistsAdminUser(): void
    {
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($this->isInstanceOf(User::class), 'password')
            ->willReturn('hashed_password');

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (User $user) {
                return $user->getEmail() === 'admin_test@example.com'
                    && in_array(Role::ADMIN->value, $user->getRoles(), true)
                    && $user->getPassword() === 'hashed_password';
            }));
        $manager->expects($this->once())->method('flush');

        $fixture = new TestAdminFixture($passwordHasher);
        $fixture->load($manager);
    }
}
