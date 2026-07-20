<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use App\Enum\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TestAdminFixture extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;
    private const string ADMIN_EMAIL_TEST = 'admin_test@example.com';

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail(self::ADMIN_EMAIL_TEST);
        $user->setRoles([Role::ADMIN->value]);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));

        $manager->persist($user);
        $manager->flush();
    }
}
