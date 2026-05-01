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

    protected function getTestUser(): User
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

    protected function createTestSport(): Sport
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

    protected function setCurrentSport(KernelBrowser $client): Sport
    {
        /** @var SportRepository $sportRepository */
        $sportRepository = static::getContainer()->get(SportRepository::class);
        $sport = $sportRepository->findOneBy([]) ?? $this->createTestSport();

        $client->request('POST', sprintf('/sports/set/%d', $sport->getId()));

        return $sport;
    }

    protected function loginAsTestUser(KernelBrowser $client): User
    {
        $user = $this->getTestUser();
        $client->loginUser($user);

        return $user;
    }

    protected function assertSport(?Sport $sport): Sport
    {
        $this->assertNotNull($sport);

        return $sport;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        return $em;
    }

    /**
     * @return array<string, mixed>
     */
    protected function assertJsonSuccessResponse(KernelBrowser $client): array
    {
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $this->assertJson($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);

        return $data;
    }

    protected function getValidCsrfToken(KernelBrowser $client, string $url): string
    {
        $crawler = $client->request('GET', $url);

        foreach ($crawler->filter('input[type="hidden"]') as $input) {
            /** @var \DOMElement $input */
            if (str_contains($input->getAttribute('name'), '_token')) {
                return $input->getAttribute('value');
            }
        }

        return '';
    }
}
