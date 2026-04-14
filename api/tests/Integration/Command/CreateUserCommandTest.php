<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\Entity\User;
use App\Enum\Role;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
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
        // Czyszczenie po każdym teście
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

    public function testCreateAdminUserSuccessfully(): void
    {
        $exitCode = $this->commandTester->execute([
            'email'    => 'integration-admin@example.com',
            'password' => 'SecurePass123!',
            'role'     => Role::ADMIN->value,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('User created successfully.', $this->commandTester->getDisplay());

        $user = $this->userRepository->findOneBy(['email' => 'integration-admin@example.com']);
        $this->assertNotNull($user);
        $this->assertContains(Role::ADMIN->value, $user->getRoles());
    }

    public function testCreateUserWithDefaultRole(): void
    {
        $kernel = self::$kernel;
        $this->assertInstanceOf(KernelInterface::class, $kernel);
        $application = new Application($kernel);

        // Utwórz admina
        $adminTester = new CommandTester($application->find('app:create-user'));
        $adminTester->execute([
            'email'    => 'integration-admin@example.com',
            'password' => 'SecurePass123!',
            'role'     => Role::ADMIN->value,
        ]);

        // Wyczyść cache EM żeby drugi command widział nowego admina
        $this->em->clear();

        // Utwórz usera z domyślną rolą
        $userTester = new CommandTester($application->find('app:create-user'));
        $exitCode = $userTester->execute([
            'email'    => 'integration-user@example.com',
            'password' => 'SecurePass123!',
        ]);

        $this->assertEquals(0, $exitCode);

        $user = $this->userRepository->findOneBy(['email' => 'integration-user@example.com']);
        $this->assertNotNull($user);
        $this->assertContains(Role::USER->value, $user->getRoles());
    }

    public function testFailsWhenEmailAlreadyExists(): void
    {
        $this->commandTester->execute([
            'email'    => 'integration-duplicate@example.com',
            'password' => 'SecurePass123!',
            'role'     => Role::ADMIN->value,
        ]);

        // Próba rejestracji tego samego emaila
        $exitCode = $this->commandTester->execute([
            'email'    => 'integration-duplicate@example.com',
            'password' => 'AnotherPass123!',
            'role'     => Role::ADMIN->value,
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Email already exists.', $this->commandTester->getDisplay());
    }

    public function testFailsWhenEmailIsInvalid(): void
    {
        $exitCode = $this->commandTester->execute([
            'email'    => 'integration-invalid-email',
            'password' => 'SecurePass123!',
            'role'     => Role::ADMIN->value,
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('email', $this->commandTester->getDisplay());

        $user = $this->userRepository->findOneBy(['email' => 'integration-invalid-email']);
        $this->assertNull($user);
    }

    public function testFailsWhenPasswordIsTooShort(): void
    {
        $exitCode = $this->commandTester->execute([
            'email'    => 'integration-admin@example.com',
            'password' => '123',
            'role'     => Role::ADMIN->value,
        ]);

        $this->assertEquals(1, $exitCode);

        $user = $this->userRepository->findOneBy(['email' => 'integration-admin@example.com']);
        $this->assertNull($user);
    }

    public function testUserPasswordIsHashed(): void
    {
        $plainPassword = 'SecurePass123!';

        $this->commandTester->execute([
            'email'    => 'integration-admin@example.com',
            'password' => $plainPassword,
            'role'     => Role::ADMIN->value,
        ]);

        $user = $this->userRepository->findOneBy(['email' => 'integration-admin@example.com']);
        $this->assertNotNull($user);
        $this->assertNotEquals($plainPassword, $user->getPassword());
        $this->assertNotEmpty($user->getPassword());
    }

    public function testUserIsPersistedInDatabase(): void
    {
        $this->commandTester->execute([
            'email'    => 'integration-admin@example.com',
            'password' => 'SecurePass123!',
            'role'     => Role::ADMIN->value,
        ]);

        $this->em->clear();

        $user = $this->userRepository->findOneBy(['email' => 'integration-admin@example.com']);
        $this->assertNotNull($user);
        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->getId());
    }
}
