<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web;

use App\Entity\MemberPosition;
use App\Entity\Person;
use App\Entity\Season;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Enum\Gender;
use App\Enum\TeamType;
use App\Repository\TeamMemberRepository;
use App\Tests\Trait\ControllerTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class TeamMemberControllerTest extends WebTestCase
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

        $client->request('GET', '/team-members');

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    #[TestDox('Index page redirects unauthenticated users to the login page')]
    public function indexRedirectsUnauthenticatedUsers(): void
    {
        $client = self::createClient();

        $client->request('GET', '/team-members');

        $this->assertResponseRedirects();
    }

    #[Test]
    #[TestDox('Index page accepts optional filter query parameters without error')]
    public function indexAcceptsFilterQueryParameters(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/team-members', ['page' => 1, 'limit' => 10]);

        $this->assertResponseIsSuccessful();
    }

    // -------------------------------------------------------------------------
    // new – GET
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('New page displays the team member form when sport is selected')]
    public function newPageDisplaysForm(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $this->setCurrentSport($client);

        $client->request('GET', '/team-members/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    #[Test]
    #[TestDox('New endpoint returns 400 when no sport is selected')]
    public function newReturnsBadRequestWhenNoSportSelected(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/team-members/new', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // -------------------------------------------------------------------------
    // new – POST (tworzenie)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the new form creates a team member and returns 201 JSON success')]
    public function submittingNewFormCreatesTeamMember(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport    = $this->setCurrentSport($client);
        $em       = $this->getEntityManager();
        $user     = $this->getTestUser();

        // Tworzymy team z tym samym sportem co setCurrentSport
        $team = new Team();
        $team->setName('Form Team ' . uniqid());
        $team->setSport($sport);
        $team->setTeamType(TeamType::CLUB);
        $team->setCreatedBy($user);
        $team->setModifiedBy($user);
        $team->setIsActive(true);
        $em->persist($team);

        // Person z tym sportem, currentTeam=null (żeby PRE_SUBMIT go znalazł dla nowego membera)
        $person = new Person();
        $person->setFirstName('Form');
        $person->setLastName('Person');
        $person->setGender(Gender::MALE);
        $person->setSport($sport);
        $person->setCreatedBy($user);
        $person->setModifiedBy($user);
        $person->setIsActive(true);
        $em->persist($person);

        $season = new Season();
        $season->setStartYear(2023);
        $season->setEndYear(2024);
        $season->setCreatedBy($user);
        $season->setModifiedBy($user);
        $season->setIsActive(true);
        $em->persist($season);

        $position = new MemberPosition();
        $position->setName('Position ' . uniqid());
        $position->setSport($sport);
        $position->setCreatedBy($user);
        $position->setModifiedBy($user);
        $position->setIsActive(true);
        $em->persist($position);

        $em->flush();

        // PRE_SUBMIT listener odbudowuje choices dla person na podstawie teamId
        $client->request('POST', '/team-members/new', [
            'team_member' => [
                'team'            => (string) $team->getId(),
                'person'          => (string) $person->getId(),
                'startSeason'     => (string) $season->getId(),
                'memberPosition'  => (string) $position->getId(),
                'isCurrentMember' => '1',
                '_token'          => $this->getValidCsrfToken($client, '/team-members/new'),
            ],
        ]);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var TeamMemberRepository $repository */
        $repository = static::getContainer()->get(TeamMemberRepository::class);
        $member     = $repository->findOneBy(['team' => $team->getId(), 'person' => $person->getId()]);

        $this->assertNotNull($member);
        $this->assertTrue($member->isActive());
    }

    // -------------------------------------------------------------------------
    // new – POST (walidacja: nieistniejący team → !isValid())
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the new form with a non-existent team ID returns 400')]
    public function submittingNewFormWithInvalidTeamReturns400(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $this->setCurrentSport($client);

        // Nieistniejące ID → EntityType TransformationFailed → isSynchronized=false → isValid=false
        $client->request('POST', '/team-members/new', [
            'team_member' => [
                'team'   => '999999',
                '_token' => $this->getValidCsrfToken($client, '/team-members/new'),
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
    #[TestDox('Edit page displays the team member form prefilled with existing data')]
    public function editPageDisplaysForm(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $team       = $this->createTestTeamEntity();
        $person     = $this->createTestPerson($team);
        $season     = $this->createTestSeason();
        $position   = $this->createTestMemberPosition($team);
        $teamMember = $this->createTestTeamMember($team, $person, $season, $position);

        $client->request('GET', sprintf('/team-members/%d/edit', $teamMember->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    // -------------------------------------------------------------------------
    // edit – POST (aktualizacja)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the edit form updates the team member in the database')]
    public function submittingEditFormUpdatesTeamMember(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $team       = $this->createTestTeamEntity();
        $person     = $this->createTestPerson($team);
        $season     = $this->createTestSeason();
        $position   = $this->createTestMemberPosition($team);
        $teamMember = $this->createTestTeamMember($team, $person, $season, $position);

        $this->assertTrue($teamMember->isCurrentMember());

        // Surowy POST z PRE_SUBMIT – isCurrentMember=false (pusty checkbox)
        $client->request('POST', sprintf('/team-members/%d/edit', $teamMember->getId()), [
            'team_member' => [
                'team'           => (string) $team->getId(),
                'person'         => (string) $person->getId(),
                'startSeason'    => (string) $season->getId(),
                'memberPosition' => (string) $position->getId(),
                '_token'         => $this->getValidCsrfToken(
                    $client,
                    sprintf('/team-members/%d/edit', $teamMember->getId())
                ),
            ],
        ]);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var TeamMemberRepository $repository */
        $repository    = static::getContainer()->get(TeamMemberRepository::class);
        $updatedMember = $repository->find($teamMember->getId());

        $this->assertNotNull($updatedMember);
        $this->assertFalse($updatedMember->isCurrentMember());
    }

    #[Test]
    #[TestDox('Submitting the edit form with a non-existent team ID returns 400')]
    public function submittingEditFormWithInvalidTeamReturns400(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $team       = $this->createTestTeamEntity();
        $person     = $this->createTestPerson($team);
        $season     = $this->createTestSeason();
        $position   = $this->createTestMemberPosition($team);
        $teamMember = $this->createTestTeamMember($team, $person, $season, $position);

        $client->request('POST', sprintf('/team-members/%d/edit', $teamMember->getId()), [
            'team_member' => [
                'team'   => '999999',
                '_token' => $this->getValidCsrfToken($client, sprintf('/team-members/%d/edit', $teamMember->getId())),
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
    #[TestDox('Delete request soft-deletes the team member by setting it as inactive')]
    public function deleteSoftDeletesTeamMember(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport    = $this->setCurrentSport($client);
        $em       = $this->getEntityManager();
        $user     = $this->getTestUser();

        $team = new Team();
        $team->setName('Del Team ' . uniqid());
        $team->setSport($sport);
        $team->setTeamType(TeamType::CLUB);
        $team->setCreatedBy($user);
        $team->setModifiedBy($user);
        $team->setIsActive(true);
        $em->persist($team);

        $person = new Person();
        $person->setFirstName('Del');
        $person->setLastName('Person');
        $person->setGender(Gender::MALE);
        $person->setSport($sport);
        $person->setCurrentTeam($team);
        $person->setCreatedBy($user);
        $person->setModifiedBy($user);
        $person->setIsActive(true);
        $em->persist($person);

        $season = new Season();
        $season->setStartYear(2022);
        $season->setEndYear(2023);
        $season->setCreatedBy($user);
        $season->setModifiedBy($user);
        $season->setIsActive(true);
        $em->persist($season);

        $position = new MemberPosition();
        $position->setName('Del Position ' . uniqid());
        $position->setSport($sport);
        $position->setCreatedBy($user);
        $position->setModifiedBy($user);
        $position->setIsActive(true);
        $em->persist($position);

        $teamMember = new TeamMember();
        $teamMember->setTeam($team);
        $teamMember->setPerson($person);
        $teamMember->setStartSeason($season);
        $teamMember->setMemberPosition($position);
        $teamMember->setIsCurrentMember(true);
        $teamMember->setCreatedBy($user);
        $teamMember->setModifiedBy($user);
        $teamMember->setIsActive(true);
        $em->persist($teamMember);
        $em->flush();

        $teamMemberId = $teamMember->getId();

        $crawler      = $client->request('GET', '/team-members');
        $deleteButton = $crawler->filter(sprintf('button[data-delete-url*="%d"]', $teamMemberId));

        $this->assertGreaterThan(0, $deleteButton->count(), 'Delete button not found');

        $client->request('POST', sprintf('/team-members/%d', $teamMemberId), [
            '_token' => $deleteButton->attr('data-token'),
        ]);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em->clear();

        /** @var TeamMemberRepository $repository */
        $repository    = static::getContainer()->get(TeamMemberRepository::class);
        $deletedMember = $repository->find($teamMemberId);

        $this->assertNotNull($deletedMember);
        $this->assertFalse($deletedMember->isActive());
    }

    #[Test]
    #[TestDox('Delete request with invalid CSRF token returns 403 and leaves team member active')]
    public function deleteWithInvalidCsrfTokenReturnsForbidden(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $team       = $this->createTestTeamEntity();
        $person     = $this->createTestPerson($team);
        $season     = $this->createTestSeason();
        $position   = $this->createTestMemberPosition($team);
        $teamMember = $this->createTestTeamMember($team, $person, $season, $position);

        $client->request('POST', sprintf('/team-members/%d', $teamMember->getId()), [
            '_token' => 'invalid_token',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var TeamMemberRepository $repository */
        $repository  = static::getContainer()->get(TeamMemberRepository::class);
        $stillActive = $repository->find($teamMember->getId());

        $this->assertNotNull($stillActive);
        $this->assertTrue($stillActive->isActive());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createTestTeamEntity(): Team
    {
        $em    = $this->getEntityManager();
        $user  = $this->getTestUser();
        $sport = $this->createTestSport();

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

    private function createTestPerson(Team $team): Person
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();
        $sport = $this->assertSport($team->getSport());

        $person = new Person();
        $person->setFirstName('Jan');
        $person->setLastName('Testowy ' . uniqid());
        $person->setGender(Gender::MALE);
        $person->setSport($sport);
        $person->setCurrentTeam($team);
        $person->setCreatedBy($user);
        $person->setModifiedBy($user);
        $person->setIsActive(true);

        $em->persist($person);
        $em->flush();

        return $person;
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

    private function createTestMemberPosition(Team $team): MemberPosition
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $position = new MemberPosition();
        $position->setName('Position ' . uniqid());
        $position->setSport($team->getSport());
        $position->setCreatedBy($user);
        $position->setModifiedBy($user);
        $position->setIsActive(true);

        $em->persist($position);
        $em->flush();

        return $position;
    }

    private function createTestTeamMember(
        Team $team,
        Person $person,
        Season $season,
        MemberPosition $position
    ): TeamMember {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $teamMember = new TeamMember();
        $teamMember->setTeam($team);
        $teamMember->setPerson($person);
        $teamMember->setStartSeason($season);
        $teamMember->setMemberPosition($position);
        $teamMember->setIsCurrentMember(true);
        $teamMember->setCreatedBy($user);
        $teamMember->setModifiedBy($user);
        $teamMember->setIsActive(true);

        $em->persist($teamMember);
        $em->flush();

        return $teamMember;
    }
}
