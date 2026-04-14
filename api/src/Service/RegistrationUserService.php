<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Request\RegistrationUserRequest;
use App\Entity\User;
use App\Enum\Role;
use App\Exception\HttpConflictException;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

readonly class RegistrationUserService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository,
    ) {
    }

    /**
     * @throws HttpConflictException|Exception
     */
    public function register(RegistrationUserRequest $dto): User
    {
        if (!is_null($this->userRepository->findOneByEmail($dto->getEmail()))) {
            throw new HttpConflictException('Email already exists.');
        }

        if ($dto->getRole() === Role::USER && $this->userRepository->isAdminExists() === false) {
            throw new HttpConflictException('Admin should be registered first');
        }

        $user = new User();
        $user->setEmail($dto->email);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->getPlainPassword());
        $user->setPassword($hashedPassword);
        $user->setRoles([$dto->getRole()->value]);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
