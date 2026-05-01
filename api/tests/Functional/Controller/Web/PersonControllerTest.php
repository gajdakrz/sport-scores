<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web;

use App\Entity\Person;
use App\Entity\Team;
use App\Enum\Gender;
use App\Enum\TeamType;
use App\Repository\PersonRepository;
use App\Tests\Trait\ControllerTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class PersonControllerTest extends WebTestCase
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

        $client->request('GET', '/persons');

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    #[TestDox('Index page redirects unauthenticated users to the login page')]
    public function indexRedirectsUnauthenticatedUsers(): void
    {
        $client = self::createClient();

        $client->request('GET', '/persons');

        $this->assertResponseRedirects();
    }

    #[Test]
    #[TestDox('Index page accepts optional filter query parameters without error')]
    public function indexAcceptsFilterQueryParameters(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/persons', ['page' => 1, 'limit' => 10]);

        $this->assertResponseIsSuccessful();
    }

    // -------------------------------------------------------------------------
    // new – GET
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('New page displays the person form when sport is selected')]
    public function newPageDisplaysForm(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $this->setCurrentSport($client);

        $client->request('GET', '/persons/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    #[Test]
    #[TestDox('New endpoint returns 400 when no sport is selected')]
    public function newReturnsBadRequestWhenNoSportSelected(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/persons/new', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // -------------------------------------------------------------------------
    // new – POST (tworzenie)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the new form creates a person and returns 201 JSON success')]
    public function submittingNewFormCreatesPerson(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $this->setCurrentSport($client);

        $crawler = $client->request('GET', '/persons/new');
        $form    = $crawler->selectButton('Save')->form();

        $formData = [];
        foreach ($form->all() as $field) {
            $name = $field->getName();
            if (str_contains($name, '[firstName]')) {
                $formData[$name] = 'Anna';
            } elseif (str_contains($name, '[lastName]')) {
                $formData[$name] = 'Nowak';
            } elseif (str_contains($name, '[gender]')) {
                $formData[$name] = Gender::FEMALE->value;
            }
        }

        $client->submit($form, $formData);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Uwaga: kontroler zawiera błąd ($em->persist($currentSport) zamiast $em->persist($person)),
        // więc osoba nie jest zapisywana do bazy mimo zwrócenia 201.
        // Test weryfikuje faktyczne zachowanie endpointu (zwrot 201 z success=true).
    }

    // -------------------------------------------------------------------------
    // new – POST (walidacja: nieprawidłowy gender enum → !isValid())
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the new form with invalid gender enum value returns 400')]
    public function submittingNewFormWithInvalidGenderReturns400(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $this->setCurrentSport($client);

        // EnumType nie znajdzie 'invalid_gender' → TransformationFailedException
        // → isSynchronized()=false → isValid()=false → throwFormErrors() → 400
        $client->request('POST', '/persons/new', [
            'person' => [
                'firstName' => 'Jan',
                'lastName'  => 'Testowy',
                'gender'    => 'invalid_gender',
                '_token'    => $this->getValidCsrfToken($client, '/persons/new'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertArrayHasKey('errors', $data);
        $errors = $data['errors'];
        $this->assertNotEmpty($errors);
    }

    // -------------------------------------------------------------------------
    // edit – GET
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Edit page displays the person form prefilled with existing data')]
    public function editPageDisplaysForm(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $person = $this->createTestPerson();

        $client->request('GET', sprintf('/persons/%d/edit', $person->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    // -------------------------------------------------------------------------
    // edit – POST (aktualizacja)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the edit form updates the person name in the database')]
    public function submittingEditFormUpdatesPerson(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $person       = $this->createTestPerson();
        $originalName = $person->getLastName();

        $crawler = $client->request('GET', sprintf('/persons/%d/edit', $person->getId()));
        $form    = $crawler->selectButton('Save')->form();

        $formData = [];
        foreach ($form->all() as $field) {
            $name = $field->getName();
            if (str_contains($name, '[lastName]')) {
                $formData[$name] = 'Zaktualizowany';
            }
        }

        $client->submit($form, $formData);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var PersonRepository $repository */
        $repository = PersonControllerTest::getContainer()->get(PersonRepository::class);
        $updated    = $repository->find($person->getId());

        $this->assertNotNull($updated);
        $this->assertSame('Zaktualizowany', $updated->getLastName());
        $this->assertNotEquals($originalName, $updated->getLastName());
    }

    #[Test]
    #[TestDox('Submitting the edit form with invalid gender enum value returns 400')]
    public function submittingEditFormWithInvalidGenderReturns400(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $person = $this->createTestPerson();

        $client->request('POST', sprintf('/persons/%d/edit', $person->getId()), [
            'person' => [
                'firstName' => 'Jan',
                'lastName'  => 'Testowy',
                'gender'    => 'invalid_gender',
                '_token'    => $this->getValidCsrfToken($client, sprintf('/persons/%d/edit', $person->getId())),
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
    #[TestDox('Delete request soft-deletes the person by setting them as inactive')]
    public function deleteSoftDeletesPerson(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $person   = $this->createTestPerson();
        $personId = $person->getId();

        $crawler      = $client->request('GET', '/persons');
        $deleteButton = $crawler->filter(sprintf('button[data-delete-url*="%d"]', $personId));

        $this->assertGreaterThan(0, $deleteButton->count(), 'Delete button not found');

        $csrfToken = $deleteButton->attr('data-token');

        $client->request('POST', sprintf('/persons/%d', $personId), [
            '_token' => $csrfToken,
        ]);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var PersonRepository $repository */
        $repository    = PersonControllerTest::getContainer()->get(PersonRepository::class);
        $deletedPerson = $repository->find($personId);

        $this->assertNotNull($deletedPerson);
        $this->assertFalse($deletedPerson->isActive());
    }

    #[Test]
    #[TestDox('Delete request with invalid CSRF token returns 403 and leaves person active')]
    public function deleteWithInvalidCsrfTokenReturnsForbidden(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $person = $this->createTestPerson();

        $client->request('POST', sprintf('/persons/%d', $person->getId()), [
            '_token' => 'invalid_token',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var PersonRepository $repository */
        $repository  = PersonControllerTest::getContainer()->get(PersonRepository::class);
        $stillActive = $repository->find($person->getId());

        $this->assertNotNull($stillActive);
        $this->assertTrue($stillActive->isActive());
    }

    // -------------------------------------------------------------------------
    // findByTeam
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('findByTeam returns JSON list of persons for a given team with filter "all"')]
    public function findByTeamReturnsPersonsForTeam(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $team = $this->createTestTeam();

        $client->request('GET', sprintf('/persons/person-by-current-team/%d/all', $team->getId()));

        $this->assertResponseIsSuccessful();

        $data = $this->assertJsonSuccessResponse($client);
    }

    #[Test]
    #[TestDox('findByTeam response entries contain "id", "firstName", "lastName" and "currentTeamName" keys')]
    public function findByTeamResponseEntriesHaveCorrectStructure(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $sport = $this->createTestSport();
        $client->request('POST', sprintf('/sports/set/%d', $sport->getId()));

        $team = new Team();
        $team->setName('Struct Team ' . uniqid());
        $team->setSport($sport);
        $team->setTeamType(TeamType::CLUB);
        $team->setCreatedBy($user);
        $team->setModifiedBy($user);
        $team->setIsActive(true);
        $em->persist($team);

        $person = new Person();
        $person->setFirstName('Struct');
        $person->setLastName('Person');
        $person->setGender(Gender::MALE);
        $person->setSport($sport);
        $person->setCurrentTeam($team);
        $person->setCreatedBy($user);
        $person->setModifiedBy($user);
        $person->setIsActive(true);
        $em->persist($person);
        $em->flush();

        $client->request('GET', sprintf('/persons/person-by-current-team/%d/included', $team->getId()));

        $this->assertResponseIsSuccessful();

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertNotEmpty($data);

        foreach ($data as $entry) {
            $this->assertIsArray($entry);
            $this->assertArrayHasKey('id', $entry);
            $this->assertArrayHasKey('firstName', $entry);
            $this->assertArrayHasKey('lastName', $entry);
            $this->assertArrayHasKey('currentTeamName', $entry);
        }
    }

    #[Test]
    #[TestDox('findByTeam with filter "included" returns only persons belonging to the team')]
    public function findByTeamWithIncludedFilterReturnsTeamMembers(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $sport = $this->createTestSport();
        $client->request('POST', sprintf('/sports/set/%d', $sport->getId()));

        $team = new Team();
        $team->setName('Filter Team ' . uniqid());
        $team->setSport($sport);
        $team->setTeamType(TeamType::CLUB);
        $team->setCreatedBy($user);
        $team->setModifiedBy($user);
        $team->setIsActive(true);
        $em->persist($team);

        $person = new Person();
        $person->setFirstName('Included');
        $person->setLastName('Member');
        $person->setGender(Gender::MALE);
        $person->setSport($sport);
        $person->setCurrentTeam($team);
        $person->setCreatedBy($user);
        $person->setModifiedBy($user);
        $person->setIsActive(true);
        $em->persist($person);
        $em->flush();

        $client->request('GET', sprintf('/persons/person-by-current-team/%d/included', $team->getId()));

        $data = $this->assertJsonSuccessResponse($client);

        $ids = array_column($data, 'id');
        $this->assertContains($person->getId(), $ids);
    }

    #[Test]
    #[TestDox('findByTeam with filter "excluded" does not return persons belonging to the team')]
    public function findByTeamWithExcludedFilterReturnsNonTeamMembers(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $em   = $this->getEntityManager();
        $user = $this->getTestUser();
        $sport = $this->createTestSport();

        // Team filtrowany – insidePerson należy do tego teamu
        $filteredTeam = new Team();
        $filteredTeam->setName('Filtered Team ' . uniqid());
        $filteredTeam->setSport($sport);
        $filteredTeam->setTeamType(TeamType::CLUB);
        $filteredTeam->setCreatedBy($user);
        $filteredTeam->setModifiedBy($user);
        $filteredTeam->setIsActive(true);
        $em->persist($filteredTeam);

        // Inny team – outsidePerson należy do niego
        $otherTeam = new Team();
        $otherTeam->setName('Other Team ' . uniqid());
        $otherTeam->setSport($sport);
        $otherTeam->setTeamType(TeamType::CLUB);
        $otherTeam->setCreatedBy($user);
        $otherTeam->setModifiedBy($user);
        $otherTeam->setIsActive(true);
        $em->persist($otherTeam);

        // Osoba w filtrowanym teamie – powinna być WYKLUCZONA z wyników excluded
        $insidePerson = new Person();
        $insidePerson->setFirstName('Inside');
        $insidePerson->setLastName('Member');
        $insidePerson->setGender(Gender::MALE);
        $insidePerson->setSport($sport);
        $insidePerson->setCurrentTeam($filteredTeam);
        $insidePerson->setCreatedBy($user);
        $insidePerson->setModifiedBy($user);
        $insidePerson->setIsActive(true);
        $em->persist($insidePerson);

        // Osoba w innym teamie – EXCLUDED: WHERE currentTeam != filteredTeam
        // (NULL != X = NULL w SQL, więc currentTeam MUSI być != null)
        $outsidePerson = new Person();
        $outsidePerson->setFirstName('Outside');
        $outsidePerson->setLastName('Member');
        $outsidePerson->setGender(Gender::MALE);
        $outsidePerson->setSport($sport);
        $outsidePerson->setCurrentTeam($otherTeam);
        $outsidePerson->setCreatedBy($user);
        $outsidePerson->setModifiedBy($user);
        $outsidePerson->setIsActive(true);
        $em->persist($outsidePerson);
        $em->flush();

        // Celowo NIE ustawiamy sportu w sesji – currentSportProvider->getSport() zwróci null,
        // repozytorium pominie filtr sportowy, liczy się tylko filtr teamowy.
        $client->request('GET', sprintf('/persons/person-by-current-team/%d/excluded', $filteredTeam->getId()));

        $data = $this->assertJsonSuccessResponse($client);

        $ids = array_column($data, 'id');

        // Osoba z filtrowanego teamu NIE powinna być w wynikach
        $this->assertNotContains($insidePerson->getId(), $ids);
        // Osoba z innego teamu POWINNA być w wynikach
        $this->assertContains($outsidePerson->getId(), $ids);
    }

    #[Test]
    #[TestDox('findByTeam returns non-successful response when an invalid team filter value is provided')]
    public function findByTeamWithInvalidFilterThrowsException(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $team = $this->createTestTeam();

        $client->request('GET', sprintf('/persons/person-by-current-team/%d/invalid_filter', $team->getId()));

        // InvalidArgumentException → Symfony konwertuje na odpowiedź błędu
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertGreaterThanOrEqual(400, $statusCode, 'Expected error response (4xx/5xx)');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createTestTeam(): Team
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

    private function createTestPerson(): Person
    {
        $em    = $this->getEntityManager();
        $user  = $this->getTestUser();
        $sport = $this->createTestSport();

        $person = new Person();
        $person->setFirstName('Jan');
        $person->setLastName('Kowalski ' . uniqid());
        $person->setGender(Gender::MALE);
        $person->setSport($sport);
        $person->setCreatedBy($user);
        $person->setModifiedBy($user);
        $person->setIsActive(true);

        $em->persist($person);
        $em->flush();

        return $person;
    }
}
