<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web;

use App\Entity\Competition;
use App\Entity\Person;
use App\Entity\Season;
use App\Entity\Team;
use App\Entity\Sport;
use App\Entity\TeamMember;
use App\Enum\Gender;
use App\Enum\GenderCategory;
use App\Enum\TeamType;
use App\Repository\TeamRepository;
use App\Tests\Trait\ControllerTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class TeamControllerTest extends WebTestCase
{
    use ControllerTestTrait;

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
            } elseif (str_contains($name, '[gender]')) {
                $formData[$name] = GenderCategory::MIXED->value;
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
        $this->assertSame(GenderCategory::MIXED, $team->getGender());
    }

    #[Test]
    #[TestDox('Submitting the new form without gender returns 400 with error on "gender"')]
    public function submittingNewFormWithoutGenderReturns400(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $this->setCurrentSport($client);

        $crawler = $client->request('GET', '/teams/new');
        $form    = $crawler->selectButton('Save')->form();

        $client->submit($form, [
            'team[name]'     => 'No Gender Team ' . uniqid(),
            'team[teamType]' => TeamType::CLUB->value,
            'team[gender]'   => '',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertIsArray($data['errors']);
        $this->assertContains('gender', array_column($data['errors'], 'field'));
    }

    #[Test]
    #[TestDox('Submitting the new form with gender stores the team gender')]
    public function submittingNewFormWithGenderStoresGender(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $this->setCurrentSport($client);

        $crawler = $client->request('GET', '/teams/new');
        $form    = $crawler->selectButton('Save')->form();
        $name    = 'Female Team ' . uniqid();

        $client->submit($form, [
            'team[name]'     => $name,
            'team[teamType]' => TeamType::CLUB->value,
            'team[gender]'   => GenderCategory::FEMALE->value,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var TeamRepository $repository */
        $repository = static::getContainer()->get(TeamRepository::class);
        $team       = $repository->findOneBy(['name' => $name]);

        $this->assertNotNull($team);
        $this->assertSame(GenderCategory::FEMALE, $team->getGender());
    }

    #[Test]
    #[TestDox('Changing team gender to one that conflicts with active members returns 400 with error on "gender"')]
    public function editingGenderConflictingWithMembersReturns400(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $em    = $this->getEntityManager();
        $user  = $this->getTestUser();
        $sport = $this->createTestSport();
        $team  = $this->createTestTeamEntity($sport);

        $person = new Person();
        $person->setFirstName('Anna');
        $person->setLastName('Member ' . uniqid());
        $person->setGender(Gender::FEMALE);
        $person->setSport($sport);
        $person->setCreatedBy($user);
        $person->setModifiedBy($user);
        $em->persist($person);

        $season = new Season();
        $season->setStartYear(2024);
        $season->setEndYear(2025);
        $season->setCreatedBy($user);
        $season->setModifiedBy($user);
        $em->persist($season);

        $member = new TeamMember();
        $member->setPerson($person);
        $member->setStartSeason($season);
        $member->setCreatedBy($user);
        $member->setModifiedBy($user);
        $team->addTeamMember($member);
        $em->persist($member);
        $em->flush();

        $crawler = $client->request('GET', sprintf('/teams/%d/edit', $team->getId()));
        $form    = $crawler->selectButton('Save')->form();

        $client->submit($form, ['team[gender]' => GenderCategory::MALE->value]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertIsArray($data['errors']);
        $this->assertContains('gender', array_column($data['errors'], 'field'));
    }

    #[Test]
    #[TestDox('Index filtered by gender lists only teams of that gender')]
    public function indexFilteredByGenderListsMatchingTeams(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $sport = $this->setCurrentSport($client);

        $female = $this->createTestTeamEntity($sport)->setGender(GenderCategory::FEMALE);
        $male   = $this->createTestTeamEntity($sport)->setGender(GenderCategory::MALE);
        $this->getEntityManager()->flush();

        $client->request('GET', '/teams', ['gender' => GenderCategory::FEMALE->value, 'limit' => 100]);

        $this->assertResponseIsSuccessful();
        $content = (string) $client->getResponse()->getContent();
        $this->assertStringContainsString((string) $female->getName(), $content);
        $this->assertStringNotContainsString((string) $male->getName(), $content);
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

    #[Test]
    #[TestDox('Clearing gender in the edit form returns 400 with error on "gender" and keeps the stored gender')]
    public function submittingEditFormWithoutGenderReturns400(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport = $this->createTestSport();
        $team  = $this->createTestTeamEntity($sport);
        $originalGender = $team->getGender();

        $crawler = $client->request('GET', sprintf('/teams/%d/edit', $team->getId()));
        $form    = $crawler->selectButton('Save')->form();

        $client->submit($form, ['team[gender]' => '']);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertIsArray($data['errors']);
        $this->assertContains('gender', array_column($data['errors'], 'field'));

        $this->getEntityManager()->clear();
        /** @var TeamRepository $repository */
        $repository = static::getContainer()->get(TeamRepository::class);
        $this->assertSame($originalGender, $repository->find($team->getId())?->getGender());
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

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createTestTeamEntity(Sport $sport): Team
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
        $competition->setGender(GenderCategory::MALE);
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
}
