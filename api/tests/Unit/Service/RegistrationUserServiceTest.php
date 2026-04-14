<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\Request\RegistrationUserRequest;
use App\Entity\User;
use App\Enum\Role;
use App\Exception\HttpConflictException;
use App\Repository\UserRepository;
use App\Service\RegistrationUserService;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationUserServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private UserRepository&MockObject $userRepository;
    private RegistrationUserService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->service = new RegistrationUserService(
            $this->em,
            $this->passwordHasher,
            $this->userRepository,
        );
    }

    public function testRegisterThrowsConflictWhenEmailAlreadyExists(): void
    {
        $dto = $this->createDto('existing@example.com', Role::ADMIN, 'password123');

        $this->userRepository
            ->method('findOneByEmail')
            ->with('existing@example.com')
            ->willReturn(new User());

        $this->expectException(HttpConflictException::class);
        $this->expectExceptionMessage('Email already exists.');

        $this->service->register($dto);
    }

    public function testRegisterThrowsConflictWhenNoAdminRegisteredYetAndRoleIsUser(): void
    {
        $dto = $this->createDto('user@example.com', Role::USER, 'password123');

        $this->userRepository
            ->method('findOneByEmail')
            ->willReturn(null);

        $this->userRepository
            ->method('isAdminExists')
            ->willReturn(false);

        $this->expectException(HttpConflictException::class);
        $this->expectExceptionMessage('Admin should be registered first');

        $this->service->register($dto);
    }

    /**
     * @throws Exception
     */
    public function testRegisterCreatesAdminSuccessfullyWhenNoAdminExists(): void
    {
        $dto = $this->createDto('admin@example.com', Role::ADMIN, 'secret');

        $this->userRepository
            ->method('findOneByEmail')
            ->willReturn(null);

        $this->userRepository
            ->method('isAdminExists')
            ->willReturn(false);

        $this->passwordHasher
            ->method('hashPassword')
            ->willReturn('hashed_secret');

        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $this->em->expects($this->once())->method('flush');

        $user = $this->service->register($dto);

        $this->assertEquals('admin@example.com', $user->getEmail());
        $this->assertContains(Role::ADMIN->value, $user->getRoles());
        $this->assertContains(Role::USER->value, $user->getRoles());
        $this->assertEquals('hashed_secret', $user->getPassword());
    }

    public function testRegisterCreatesUserSuccessfullyWhenAdminAlreadyExists(): void
    {
        $dto = $this->createDto('user@example.com', Role::USER, 'password123');

        $this->userRepository
            ->method('findOneByEmail')
            ->willReturn(null);

        $this->userRepository
            ->method('isAdminExists')
            ->willReturn(true);

        $this->passwordHasher
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $this->em->expects($this->once())->method('flush');

        $user = $this->service->register($dto);

        $this->assertEquals('user@example.com', $user->getEmail());
        $this->assertContains(Role::USER->value, $user->getRoles());
    }

    public function testRegisterHashesPasswordBeforeSaving(): void
    {
        $dto = $this->createDto('user@example.com', Role::ADMIN, 'plaintext');

        $this->userRepository->method('findOneByEmail')->willReturn(null);
        $this->userRepository->method('isAdminExists')->willReturn(true);

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($this->isInstanceOf(User::class), 'plaintext')
            ->willReturn('hashed_value');

        $user = $this->service->register($dto);

        $this->assertEquals('hashed_value', $user->getPassword());
    }

    /**
     * @throws Exception
     */
    public function testRegisterDoesNotPersistWhenEmailAlreadyExists(): void
    {
        $dto = $this->createDto('existing@example.com', Role::ADMIN, 'password');

        $this->userRepository->method('findOneByEmail')->willReturn(new User());

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');

        $this->expectException(HttpConflictException::class);

        $this->service->register($dto);
    }

    private function createDto(string $email, Role $role, string $plainPassword): RegistrationUserRequest
    {
        $dto = $this->createMock(RegistrationUserRequest::class);
        $dto->email = $email;
        $dto->method('getEmail')->willReturn($email);
        $dto->method('getRole')->willReturn($role);
        $dto->method('getPlainPassword')->willReturn($plainPassword);

        return $dto;
    }
}
