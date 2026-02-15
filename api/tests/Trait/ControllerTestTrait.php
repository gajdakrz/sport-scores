<?php

declare(strict_types=1);

namespace App\Tests\Trait;

use App\Entity\Sport;
use App\Entity\User;
use App\Repository\SportRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait ControllerTestTrait
{
    private const string USER_EMAIL_TEST = 'test@example.com';

    private function getTestUser(): User
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => self::USER_EMAIL_TEST]);

        if (!$user) {
            throw new \RuntimeException(
                sprintf('Test user with email "%s" not found. Make sure fixtures are loaded.', self::USER_EMAIL_TEST)
            );
        }

        return $user;
    }

    private function createTestSport(): Sport
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $this->getTestUser();

        $sport = new Sport();
        $sport->setName('Test Sport ' . uniqid());
        $sport->setCreatedBy($user);
        $sport->setModifiedBy($user);

        $em->persist($sport);
        $em->flush();

        return $sport;
    }

    private function setCurrentSport(KernelBrowser $client): Sport
    {
        /** @var SportRepository $sportRepository */
        $sportRepository = static::getContainer()->get(SportRepository::class);
        $sport = $sportRepository->findOneBy([]) ?? $this->createTestSport();

        // Ustaw sport przez endpoint (tak jak robi to aplikacja)
        $client->request('POST', sprintf('/sports/set/%d', $sport->getId()));

        return $sport;
    }

    private function loginAsTestUser(KernelBrowser $client): User
    {
        $user = $this->getTestUser();
        $client->loginUser($user);

        return $user;
    }
}
