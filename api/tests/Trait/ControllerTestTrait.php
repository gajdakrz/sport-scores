<?php

declare(strict_types=1);

namespace App\Tests\Trait;

use App\Entity\Competition;
use App\Entity\Country;
use App\Entity\Sport;
use App\Entity\Team;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Enum\GenderCategory;
use App\Enum\TeamType;
use App\Repository\SportRepository;
use App\Repository\UserRepository;
use App\Tests\Exception\FixtureNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

trait ControllerTestTrait
{
    private const string USER_EMAIL_TEST = 'test@example.com';
    private const string ADMIN_EMAIL_TEST = 'admin_test@example.com';

    protected function getTestUser(): User
    {
        return $this->findFixtureUser(self::USER_EMAIL_TEST);
    }

    protected function getTestAdmin(): User
    {
        return $this->findFixtureUser(self::ADMIN_EMAIL_TEST);
    }

    private function findFixtureUser(string $email): User
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            throw new FixtureNotFoundException(
                sprintf('Test user with email "%s" not found. Make sure fixtures are loaded.', $email)
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

    protected function createTestCountry(?string $name = null): Country
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $country = new Country();
        $country->setName($name ?? 'Test Country ' . uniqid());
        $country->setCreatedBy($user);
        $country->setModifiedBy($user);
        $country->setIsActive(true);

        $em->persist($country);
        $em->flush();

        return $country;
    }

    protected function createTeamInSport(Sport $sport): Team
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $team = new Team();
        $team->setName('Test Team ' . uniqid());
        $team->setSport($sport);
        $team->setTeamType(TeamType::CLUB);
        $team->setGender(GenderCategory::MALE);
        $team->setCreatedBy($user);
        $team->setModifiedBy($user);

        $em->persist($team);
        $em->flush();

        return $team;
    }

    protected function createCompetitionInSport(Sport $sport): Competition
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $competition = new Competition();
        $competition->setName('Test Competition ' . uniqid());
        $competition->setSport($sport);
        $competition->setGender(GenderCategory::MALE);
        $competition->setCreatedBy($user);
        $competition->setModifiedBy($user);

        $em->persist($competition);
        $em->flush();

        return $competition;
    }

    protected function saveUserPreference(
        User $user,
        ?Sport $favouriteSport,
        ?Team $favouriteTeam = null,
        bool $currentSeasonByDefault = true,
        ?Competition $favouriteCompetition = null,
    ): UserPreference {
        $em = $this->getEntityManager();

        $preference = (new UserPreference($user))
            ->update($favouriteSport, $favouriteCompetition, $favouriteTeam, $currentSeasonByDefault);
        $em->persist($preference);
        $em->flush();

        return $preference;
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

    protected function loginAsTestAdmin(KernelBrowser $client): User
    {
        $admin = $this->getTestAdmin();
        $client->loginUser($admin);

        return $admin;
    }

    protected function assertSport(?Sport $sport): Sport
    {
        $this->assertNotNull($sport);

        return $sport;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface */
        return static::getContainer()->get(EntityManagerInterface::class);
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

    /**
     * Submits a form as POST with a valid CSRF token taken from the GET version of the same URL.
     *
     * @param array<string, mixed> $fields
     */
    protected function submitFormData(KernelBrowser $client, string $url, string $formName, array $fields): void
    {
        $client->request('POST', $url, [
            $formName => $fields + ['_token' => $this->getValidCsrfToken($client, $url)],
        ]);
    }

    protected function assertDuplicateRejected(KernelBrowser $client, string $field): void
    {
        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertIsArray($data['errors']);
        $this->assertContains($field, array_column($data['errors'], 'field'));
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
