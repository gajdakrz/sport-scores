<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web;

use App\Entity\Competition;
use App\Enum\Gender;
use App\Repository\CompetitionRepository;
use App\Tests\Trait\ControllerTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CompetitionControllerTest extends WebTestCase
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

    private function createTestCompetition(): Competition
    {
        $em    = $this->getEntityManager();
        $user  = $this->getTestUser();
        $sport = $this->createTestSport();

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

        $client->request('GET', '/competitions');

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    #[TestDox('Index page redirects unauthenticated users to the login page')]
    public function indexRedirectsUnauthenticatedUsers(): void
    {
        $client = self::createClient();

        $client->request('GET', '/competitions');

        $this->assertResponseRedirects();
    }

    // -------------------------------------------------------------------------
    // new – GET
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('New page displays the competition form when sport is selected')]
    public function newPageDisplaysForm(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $this->setCurrentSport($client);

        $client->request('GET', '/competitions/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    #[Test]
    #[TestDox('New endpoint returns 400 when no sport is selected')]
    public function newReturnsBadRequestWhenNoSportSelected(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/competitions/new', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // -------------------------------------------------------------------------
    // new – POST (tworzenie)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the new form creates a competition and returns JSON success')]
    public function submittingNewFormCreatesCompetition(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $sport = $this->setCurrentSport($client);

        $crawler = $client->request('GET', '/competitions/new');
        $form    = $crawler->selectButton('Save')->form();

        $formData = [];
        foreach ($form->all() as $field) {
            $name = $field->getName();
            if (str_contains($name, '[name]')) {
                $formData[$name] = 'New Competition';
            } elseif (str_contains($name, '[gender]')) {
                $formData[$name] = Gender::MALE->value;
            }
        }

        $client->submit($form, $formData);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var CompetitionRepository $repository */
        $repository  = static::getContainer()->get(CompetitionRepository::class);
        $competition = $repository->findOneBy(['name' => 'New Competition']);

        $this->assertNotNull($competition);
        $this->assertEquals($sport->getId(), $competition->getSport()?->getId());
        $this->assertTrue($competition->isActive());
    }


    #[Test]
    #[TestDox('Submitting the new form with invalid data returns 400 with validation errors')]
    public function submittingNewFormWithInvalidDataReturnsValidationErrors(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $this->setCurrentSport($client);

        $crawler = $client->request('GET', '/competitions/new');
        $form    = $crawler->selectButton('Save')->form();

        // DomCrawler->submit() waliduje wartości przed wysłaniem, więc nieprawidłowy
        // gender trzeba wysłać surowym POST-em. Wyciągamy token CSRF z formularza
        // i budujemy request ręcznie, omijając walidację po stronie klienta.
        $csrfToken = null;
        foreach ($form->all() as $field) {
            if (str_contains($field->getName(), '[_token]')) {
                $csrfToken = $field->getValue();
            }
        }

        $client->request('POST', '/competitions/new', [
            'competition' => [
                'name'      => 'Valid Name',
                'gender'    => 'invalid_enum_value', // nie istnieje w Gender → isSynchronized() = false
                'isBracket' => false,
                '_token'    => $csrfToken,
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
    #[TestDox('Edit page displays the competition form prefilled with existing data')]
    public function editPageDisplaysForm(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $competition = $this->createTestCompetition();

        $client->request('GET', sprintf('/competitions/%d/edit', $competition->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    // -------------------------------------------------------------------------
    // edit – POST (aktualizacja)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the edit form updates the competition name in the database')]
    public function submittingEditFormUpdatesCompetition(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $competition  = $this->createTestCompetition();
        $originalName = $competition->getName();

        $crawler = $client->request('GET', sprintf('/competitions/%d/edit', $competition->getId()));
        $form    = $crawler->selectButton('Save')->form();

        $formData = [];
        foreach ($form->all() as $field) {
            $name = $field->getName();
            if (str_contains($name, '[name]')) {
                $formData[$name] = 'Updated Competition Name';
            }
        }

        $client->submit($form, $formData);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var CompetitionRepository $repository */
        $repository  = static::getContainer()->get(CompetitionRepository::class);
        $updated     = $repository->find($competition->getId());

        $this->assertNotNull($updated);
        $this->assertSame('Updated Competition Name', $updated->getName());
        $this->assertNotEquals($originalName, $updated->getName());
    }


    #[Test]
    #[TestDox('Submitting the edit form with invalid data returns 400 with validation errors')]
    public function submittingEditFormWithInvalidDataReturnsValidationErrors(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $competition = $this->createTestCompetition();

        $crawler = $client->request('GET', sprintf('/competitions/%d/edit', $competition->getId()));
        $form    = $crawler->selectButton('Save')->form();

        // Analogicznie jak w new: surowy POST omija walidację DomCrawlera.
        $csrfToken = null;
        foreach ($form->all() as $field) {
            if (str_contains($field->getName(), '[_token]')) {
                $csrfToken = $field->getValue();
            }
        }

        $client->request('POST', sprintf('/competitions/%d/edit', $competition->getId()), [
            'competition' => [
                'name'      => 'Valid Name',
                'gender'    => 'invalid_enum_value', // nie istnieje w Gender → isSynchronized() = false
                'isBracket' => false,
                '_token'    => $csrfToken,
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
    // delete
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Delete request soft-deletes the competition by setting it as inactive')]
    public function deleteSoftDeletesCompetition(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $competition   = $this->createTestCompetition();
        $competitionId = $competition->getId();

        $crawler      = $client->request('GET', '/competitions');
        $deleteButton = $crawler->filter(sprintf('button[data-delete-url*="%d"]', $competitionId));

        $this->assertGreaterThan(0, $deleteButton->count(), 'Delete button not found');

        $csrfToken = $deleteButton->attr('data-token');

        $client->request('POST', sprintf('/competitions/%d', $competitionId), [
            '_token' => $csrfToken,
        ]);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var CompetitionRepository $repository */
        $repository = static::getContainer()->get(CompetitionRepository::class);
        $deleted    = $repository->find($competitionId);

        $this->assertNotNull($deleted);
        $this->assertFalse($deleted->isActive());
    }

    #[Test]
    #[TestDox('Delete request with invalid CSRF token returns 403 and leaves competition active')]
    public function deleteWithInvalidCsrfTokenReturnsForbidden(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $competition = $this->createTestCompetition();

        $client->request('POST', sprintf('/competitions/%d', $competition->getId()), [
            '_token' => 'invalid_token',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertFalse($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var CompetitionRepository $repository */
        $repository = static::getContainer()->get(CompetitionRepository::class);
        $stillActive = $repository->find($competition->getId());

        $this->assertNotNull($stillActive);
        $this->assertTrue($stillActive->isActive());
    }

    // -------------------------------------------------------------------------
    // bySport
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Returns JSON list of competitions for a given sport ID')]
    public function bySportReturnsCompetitionsForSport(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $competition = $this->createTestCompetition();
        $sportId     = $competition->getSport()?->getId();

        $client->request('GET', sprintf('/competitions/by-sport/%d', $sportId));

        $this->assertResponseIsSuccessful();

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);

        $ids = array_column($data, 'id');
        $this->assertContains($competition->getId(), $ids);
    }

    #[Test]
    #[TestDox('Each entry in bySport response contains "id" and "name" keys')]
    public function bySportResponseEntriesHaveCorrectStructure(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $competition = $this->createTestCompetition();
        $sportId     = $competition->getSport()?->getId();

        $client->request('GET', sprintf('/competitions/by-sport/%d', $sportId));

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertIsArray($data);

        foreach ($data as $entry) {
            $this->assertIsArray($entry);
            $this->assertArrayHasKey('id', $entry);
            $this->assertArrayHasKey('name', $entry);
        }
    }

    #[Test]
    #[TestDox('Returns empty array for a sport ID with no competitions')]
    public function bySportReturnsEmptyArrayForUnknownSport(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/competitions/by-sport/999999');

        $this->assertResponseIsSuccessful();

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }
}
