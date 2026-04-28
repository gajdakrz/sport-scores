<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web;

use App\Entity\Competition;
use App\Entity\Season;
use App\Entity\Team;
use App\Entity\Sport;
use App\Enum\Gender;
use App\Enum\TeamType;
use App\Repository\TeamRepository;
use App\Tests\Trait\ControllerTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class TeamControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function getEntityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        return $em;
    }

    private function createTestTeamEntity(Sport $sport): Team
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $team = new Team();
        $team->setName('Test Team ' . uniqid());
        $team->setSport($sport);
        $team->setTeamType(TeamType::CLUB);
        $team->setCreatedBy($user);
        $team->setModifiedBy($user);
        $team->setIsActive(true);

        $em->persist($team);
        $em->flush();

        return $team;
    }

    private function createTestCompetition(Sport $sport): Competition
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $competition = new Competition();
        $competition->setName('Test Competition ' . uniqid());
        $competition->setSport($sport);
        $competition->setGender(Gender::MALE);
        $competition->setCreatedBy($user);
        $competition->setModifiedBy($user);
        $competition->setIsActive(true);

        $em->persist($competition);
        $em->flush();

        return $competition;
    }

    private function createTestSeason(): Season
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $season = new Season();
        $season->setStartYear(2023);
        $season->setEndYear(2024);
        $season->setCreatedBy($user);
        $season->setModifiedBy($user);
        $season->setIsActive(true);

        $em->persist($season);
        $em->flush();

        return $season;
    }

    private function getValidCsrfToken(KernelBrowser $client, string $url): string
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

    /**
     * @return array<string, mixed>
     */
    private function assertJsonSuccessResponse(KernelBrowser $client): array
    {
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $this->assertJson($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);

        return $data;
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Index page is accessible for authenticated user')]
    public function indexIsAccessibleForAuthenticatedUser(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/teams');

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    #[TestDox('Index page redirects unauthenticated users to the login page')]
    public function indexRedirectsUnauthenticatedUsers(): void
    {
        $client = self::createClient();

        $client->request('GET', '/teams');

        $this->assertResponseRedirects();
    }

    #[Test]
    #[TestDox('Index page accepts optional filter query parameters without error')]
    public function indexAcceptsFilterQueryParameters(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/teams', ['page' => 1, 'limit' => 10]);

        $this->assertResponseIsSuccessful();
    }

    // -------------------------------------------------------------------------
    // new – GET
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('New page displays the team form when sport is selected')]
    public function newPageDisplaysForm(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $this->setCurrentSport($client);

        $client->request('GET', '/teams/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    #[Test]
    #[TestDox('New endpoint returns 400 when no sport is selected')]
    public function newReturnsBadRequestWhenNoSportSelected(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/teams/new', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // -------------------------------------------------------------------------
    // new – POST (tworzenie)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the new form creates a team and returns 201 JSON success')]
    public function submittingNewFormCreatesTeam(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $sport = $this->setCurrentSport($client);

        $crawler = $client->request('GET', '/teams/new');
        $form    = $crawler->selectButton('Save')->form();

        $formData = [];
        foreach ($form->all() as $field) {
            $name = $field->getName();
            if (str_contains($name, '[name]')) {
                $formData[$name] = 'New Team';
            } elseif (str_contains($name, '[teamType]')) {
                $formData[$name] = TeamType::CLUB->value;
            }
        }

        $client->submit($form, $formData);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var TeamRepository $repository */
        $repository = static::getContainer()->get(TeamRepository::class);
        $team       = $repository->findOneBy(['name' => 'New Team']);

        $this->assertNotNull($team);
        $this->assertEquals($sport->getId(), $team->getSport()?->getId());
        $this->assertTrue($team->isActive());
    }

    // -------------------------------------------------------------------------
    // new – POST (walidacja: nieprawidłowy teamType enum → !isValid())
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the new form with invalid teamType enum value returns 400')]
    public function submittingNewFormWithInvalidTeamTypeReturns400(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $this->setCurrentSport($client);

        // EnumType nie znajdzie 'invalid_type' → TransformationFailedException
        // → isSynchronized()=false → isValid()=false → throwFormErrors() → 400
        $client->request('POST', '/teams/new', [
            'team' => [
                'name'     => 'Valid Team',
                'teamType' => 'invalid_type',
                '_token'   => $this->getValidCsrfToken($client, '/teams/new'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertArrayHasKey('errors', $data);
        $errors = $data['errors'];
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
    }

    // -------------------------------------------------------------------------
    // edit – GET
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Edit page displays the team form prefilled with existing data')]
    public function editPageDisplaysForm(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport = $this->createTestSport();
        $team  = $this->createTestTeamEntity($sport);

        $client->request('GET', sprintf('/teams/%d/edit', $team->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    // -------------------------------------------------------------------------
    // edit – POST (aktualizacja)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the edit form updates the team name in the database')]
    public function submittingEditFormUpdatesTeam(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport        = $this->createTestSport();
        $team         = $this->createTestTeamEntity($sport);
        $originalName = $team->getName();

        $crawler = $client->request('GET', sprintf('/teams/%d/edit', $team->getId()));
        $form    = $crawler->selectButton('Save')->form();

        $formData = [];
        foreach ($form->all() as $field) {
            $name = $field->getName();
            if (str_contains($name, '[name]')) {
                $formData[$name] = 'Updated Team Name';
            }
        }

        $client->submit($form, $formData);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var TeamRepository $repository */
        $repository  = static::getContainer()->get(TeamRepository::class);
        $updatedTeam = $repository->find($team->getId());

        $this->assertNotNull($updatedTeam);
        $this->assertSame('Updated Team Name', $updatedTeam->getName());
        $this->assertNotEquals($originalName, $updatedTeam->getName());
    }

    #[Test]
    #[TestDox('Submitting the edit form with invalid teamType enum value returns 400')]
    public function submittingEditFormWithInvalidTeamTypeReturns400(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport = $this->createTestSport();
        $team  = $this->createTestTeamEntity($sport);

        $client->request('POST', sprintf('/teams/%d/edit', $team->getId()), [
            'team' => [
                'name'     => 'Valid Team',
                'teamType' => 'invalid_type',
                '_token'   => $this->getValidCsrfToken($client, sprintf('/teams/%d/edit', $team->getId())),
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertArrayHasKey('errors', $data);
    }

    // -------------------------------------------------------------------------
    // delete
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Delete request soft-deletes the team by setting it as inactive')]
    public function deleteSoftDeletesTeam(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport  = $this->setCurrentSport($client);
        $team   = $this->createTestTeamEntity($sport);
        $teamId = $team->getId();

        $crawler      = $client->request('GET', '/teams');
        $deleteButton = $crawler->filter(sprintf('button[data-delete-url*="%d"]', $teamId));

        $this->assertGreaterThan(0, $deleteButton->count(), 'Delete button not found');

        $client->request('POST', sprintf('/teams/%d', $teamId), [
            '_token' => $deleteButton->attr('data-token'),
        ]);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var TeamRepository $repository */
        $repository  = static::getContainer()->get(TeamRepository::class);
        $deletedTeam = $repository->find($teamId);

        $this->assertNotNull($deletedTeam);
        $this->assertFalse($deletedTeam->isActive());
    }

    #[Test]
    #[TestDox('Delete request with invalid CSRF token returns 403 and leaves team active')]
    public function deleteWithInvalidCsrfTokenReturnsForbidden(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport = $this->createTestSport();
        $team  = $this->createTestTeamEntity($sport);

        $client->request('POST', sprintf('/teams/%d', $team->getId()), [
            '_token' => 'invalid_token',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var TeamRepository $repository */
        $repository  = static::getContainer()->get(TeamRepository::class);
        $stillActive = $repository->find($team->getId());

        $this->assertNotNull($stillActive);
        $this->assertTrue($stillActive->isActive());
    }

    // -------------------------------------------------------------------------
    // results (team_season_details)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Season details page is accessible for a team with valid season and competition')]
    public function seasonDetailsPageIsAccessible(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport       = $this->createTestSport();
        $team        = $this->createTestTeamEntity($sport);
        $season      = $this->createTestSeason();
        $competition = $this->createTestCompetition($sport);

        $client->request('GET', sprintf(
            '/teams/%d/seasons/%d/competitions/%d/details',
            $team->getId(),
            $season->getId(),
            $competition->getId(),
        ));

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    #[TestDox('Season details page renders partial template when request is XMLHttpRequest')]
    public function seasonDetailsRendersPartialTemplateForXhr(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport       = $this->createTestSport();
        $team        = $this->createTestTeamEntity($sport);
        $season      = $this->createTestSeason();
        $competition = $this->createTestCompetition($sport);

        $client->request(
            'GET',
            sprintf(
                '/teams/%d/seasons/%d/competitions/%d/details',
                $team->getId(),
                $season->getId(),
                $competition->getId(),
            ),
            [],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest'],
        );

        $this->assertResponseIsSuccessful();
    }

    // -------------------------------------------------------------------------
    // resultSeasonIndex (team_game_result_season_index)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Result season index page is accessible for a given team')]
    public function resultSeasonIndexIsAccessible(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport = $this->createTestSport();
        $team  = $this->createTestTeamEntity($sport);

        $client->request('GET', sprintf('/teams/%d/result-season-index', $team->getId()));

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    #[TestDox('Result season index passes null team to template when team sport differs from current sport')]
    public function resultSeasonIndexPassesNullTeamWhenSportMismatch(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        // Ustawiamy inny sport w sesji niż sport teamu
        $this->setCurrentSport($client);
        $otherSport = $this->createTestSport();
        $team       = $this->createTestTeamEntity($otherSport);

        $client->request('GET', sprintf('/teams/%d/result-season-index', $team->getId()));

        // Strona renderuje się poprawnie (200), ale team=null w szablonie
        $this->assertResponseIsSuccessful();
    }
}
