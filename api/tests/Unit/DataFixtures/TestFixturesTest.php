<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataFixtures;

use App\DataFixtures\TestAdminFixture;
use App\DataFixtures\TestUserFixture;
use App\Entity\User;
use App\Enum\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Fixtures are loaded by doctrine:fixtures:load outside PHPUnit (make test-db-ide),
 * so they are covered here directly.
 */
final class TestFixturesTest extends TestCase
{
    /**
     * @return array<string, array{class-string<TestAdminFixture|TestUserFixture>, string, Role, string}>
     */
    public static function fixtureProvider(): array
    {
        return [
            'admin' => [TestAdminFixture::class, 'admin_test@example.com', Role::ADMIN, 'Admin Test'],
            'user'  => [TestUserFixture::class, 'test@example.com', Role::USER, 'User Test'],
        ];
    }

    /**
     * @param class-string<TestAdminFixture|TestUserFixture> $fixtureClass
     */
    #[Test]
    #[DataProvider('fixtureProvider')]
    #[TestDox('$_dataName fixture persists a user with its name, role and a hashed password')]
    public function loadPersistsUserWithRoleAndHashedPassword(
        string $fixtureClass,
        string $email,
        Role $role,
        string $fullName,
    ): void {
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($this->isInstanceOf(User::class), 'password')
            ->willReturn('hashed_password');

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())
            ->method('persist')
            ->with($this->callback(
                fn(User $user): bool => $user->getEmail() === $email
                    && in_array($role->value, $user->getRoles(), true)
                    && $user->getPassword() === 'hashed_password'
                    && $user->getFullName() === $fullName
            ));
        $manager->expects($this->once())->method('flush');

        /** @var Fixture $fixture */
        $fixture = new $fixtureClass($passwordHasher);
        $fixture->load($manager);
    }
}
