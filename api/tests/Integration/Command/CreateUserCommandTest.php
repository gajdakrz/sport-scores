<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\Entity\User;
use App\Enum\Role;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

class CreateUserCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;
    private EntityManagerInterface $em;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $command = $application->find('app:create-user');
        $this->commandTester = new CommandTester($command);
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $this->assertInstanceOf(EntityManagerInterface::class, $em);
        $this->em = $em;
        $userRepository = $container->get(UserRepository::class);
        $this->assertInstanceOf(UserRepository::class, $userRepository);
        $this->userRepository = $userRepository;
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        $users = $this->userRepository->findBy(['email' => [
            'integration-admin@example.com',
            'integration-user@example.com',
            'integration-duplicate@example.com',
            'integration-invalid-email',
        ]]);

        foreach ($users as $user) {
            $this->em->remove($user);
        }

        $this->em->flush();
        $this->em->clear();

        parent::tearDown();
    }

    #[Test]
    #[TestDox('Creates admin user successfully and persists it with correct role')]
    public function createAdminUserSuccessfully(): void
    {
        $exitCode = $this->commandTester->execute([
            'email'    => 'integration-admin@example.com',
            'password' => 'SecurePass123!',
            'firstName' => 'Jan',
            'lastName' => 'Kowalski',
            'role'     => Role::ADMIN->value,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('User created successfully.', $this->commandTester->getDisplay());

        $user = $this->userRepository->findOneBy(['email' => 'integration-admin@example.com']);
        $this->assertNotNull($user);
        $this->assertContains(Role::ADMIN->value, $user->getRoles());
        $this->assertSame('Jan Kowalski', $user->getFullName());
    }

    #[Test]
    #[TestDox('Creates user with default role when no role argument is provided')]
    public function createUserWithDefaultRole(): void
    {
        $kernel = self::$kernel;
        $this->assertInstanceOf(KernelInterface::class, $kernel);
        $application = new Application($kernel);

        // Create an admin
        $adminTester = new CommandTester($application->find('app:create-user'));
        $adminTester->execute([
            'email'    => 'integration-admin@example.com',
            'password' => 'SecurePass123!',
            'firstName' => 'Jan',
            'lastName' => 'Kowalski',
            'role'     => Role::ADMIN->value,
        ]);

        // Clear the EM so the second command sees the new admin
        $this->em->clear();

        // Create a user with the default role
        $userTester = new CommandTester($application->find('app:create-user'));
        $exitCode = $userTester->execute([
            'email'    => 'integration-user@example.com',
            'password' => 'SecurePass123!',
            'firstName' => 'Jan',
            'lastName' => 'Kowalski',
        ]);

        $this->assertEquals(0, $exitCode);

        $user = $this->userRepository->findOneBy(['email' => 'integration-user@example.com']);
        $this->assertNotNull($user);
        $this->assertContains(Role::USER->value, $user->getRoles());
    }

    #[Test]
    #[TestDox('Fails with exit code 1 and error message when email already exists')]
    public function failsWhenEmailAlreadyExists(): void
    {
        $this->commandTester->execute([
            'email'    => 'integration-duplicate@example.com',
            'password' => 'SecurePass123!',
            'firstName' => 'Jan',
            'lastName' => 'Kowalski',
            'role'     => Role::ADMIN->value,
        ]);

        // Try to register the same email again
        $exitCode = $this->commandTester->execute([
            'email'    => 'integration-duplicate@example.com',
            'password' => 'AnotherPass123!',
            'firstName' => 'Jan',
            'lastName' => 'Kowalski',
            'role'     => Role::ADMIN->value,
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Email already exists.', $this->commandTester->getDisplay());
    }

    #[Test]
    #[TestDox('Fails with exit code 1 and does not persist user when email is invalid')]
    public function failsWhenEmailIsInvalid(): void
    {
        $exitCode = $this->commandTester->execute([
            'email'    => 'integration-invalid-email',
            'password' => 'SecurePass123!',
            'firstName' => 'Jan',
            'lastName' => 'Kowalski',
            'role'     => Role::ADMIN->value,
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('email', $this->commandTester->getDisplay());

        $user = $this->userRepository->findOneBy(['email' => 'integration-invalid-email']);
        $this->assertNull($user);
    }

    #[Test]
    #[TestDox('Fails with exit code 1 and does not persist user when password is too short')]
    public function failsWhenPasswordIsTooShort(): void
    {
        $exitCode = $this->commandTester->execute([
            'email'    => 'integration-admin@example.com',
            'password' => '123',
            'firstName' => 'Jan',
            'lastName' => 'Kowalski',
            'role'     => Role::ADMIN->value,
        ]);

        $this->assertEquals(1, $exitCode);

        $user = $this->userRepository->findOneBy(['email' => 'integration-admin@example.com']);
        $this->assertNull($user);
    }

    #[Test]
    #[TestDox('Fails with exit code 1 and does not persist user when first name is blank')]
    public function failsWhenFirstNameIsBlank(): void
    {
        $exitCode = $this->commandTester->execute([
            'email'     => 'integration-admin@example.com',
            'password'  => 'SecurePass123!',
            'firstName' => ' ',
            'lastName'  => 'Kowalski',
            'role'      => Role::ADMIN->value,
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('firstName', $this->commandTester->getDisplay());
        $this->assertNull($this->userRepository->findOneBy(['email' => 'integration-admin@example.com']));
    }

    #[Test]
    #[TestDox('Stored password is hashed and does not match the plain text input')]
    public function userPasswordIsHashed(): void
    {
        $plainTextInput = 'SecurePass123!';

        $this->commandTester->execute([
            'email'    => 'integration-admin@example.com',
            'password' => $plainTextInput,
            'firstName' => 'Jan',
            'lastName' => 'Kowalski',
            'role'     => Role::ADMIN->value,
        ]);

        $user = $this->userRepository->findOneBy(['email' => 'integration-admin@example.com']);
        $this->assertNotNull($user);
        $this->assertNotEquals($plainTextInput, $user->getPassword());
        $this->assertNotEmpty($user->getPassword());
    }

    #[Test]
    #[TestDox('User entity is persisted in the database with a non-null ID')]
    public function userIsPersistedInDatabase(): void
    {
        $this->commandTester->execute([
            'email'    => 'integration-admin@example.com',
            'password' => 'SecurePass123!',
            'firstName' => 'Jan',
            'lastName' => 'Kowalski',
            'role'     => Role::ADMIN->value,
        ]);

        $this->em->clear();

        $user = $this->userRepository->findOneBy(['email' => 'integration-admin@example.com']);
        $this->assertNotNull($user);
        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->getId());
    }
}
