<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\RegistrationUserRequest;
use App\Entity\User;
use App\Exception\HttpConflictException;
use App\Exception\CustomBadRequestException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationUserService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
    ) {}

    public function register(RegistrationUserRequest $dto): User
    {
        if (!is_null($this->userRepository->findOneByEmail($dto->getEmail()))) {
            throw new HttpConflictException('Email already exists.');
        }

        if ($this->userRepository->isFirstAdmin() === false) {
            throw new HttpConflictException('Admin should be registered first');
        }

        $user = new User();
        $user->setEmail($dto->email);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->plainPassword);
        $user->setPassword($hashedPassword);

        $user->setRoles(['ROLE_USER']);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
