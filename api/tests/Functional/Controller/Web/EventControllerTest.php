<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web;

use App\Entity\Competition;
use App\Entity\Event;
use App\Entity\Sport;
use App\Enum\Gender;
use App\Repository\EventRepository;
use App\Tests\Trait\ControllerTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class EventControllerTest extends WebTestCase
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

    private function createTestCompetition(Sport $sport, bool $isBracket = false): Competition
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $competition = new Competition();
        $competition->setName('Test Competition ' . uniqid());
        $competition->setSport($sport);
        $competition->setGender(Gender::MALE);
        $competition->setIsBracket($isBracket);
        $competition->setCreatedBy($user);
        $competition->setModifiedBy($user);
        $competition->setIsActive(true);

        $em->persist($competition);
        $em->flush();

        return $competition;
    }

    private function createTestEvent(Competition $competition, ?int $orderIndex = null): Event
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $event = new Event();
        $event->setName('Test Event ' . uniqid());
        $event->setCompetition($competition);
        $event->setOrderIndex($orderIndex);
        $event->setCreatedBy($user);
        $event->setModifiedBy($user);
        $event->setIsActive(true);

        $em->persist($event);
        $em->flush();

        return $event;
    }

    private function getValidCsrfToken(KernelBrowser $client, string $url): string
    {
        $crawler = $client->request('GET', $url);

        foreach ($crawler->filter('input[type="hidden"]') as $input) {
            /** @var \DOMElement $input */
            $name = $input->getAttribute('name');
            if (str_contains($name, '_token')) {
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

        $client->request('GET', '/events');

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    #[TestDox('Index page redirects unauthenticated users to the login page')]
    public function indexRedirectsUnauthenticatedUsers(): void
    {
        $client = self::createClient();

        $client->request('GET', '/events');

        $this->assertResponseRedirects();
    }

    #[Test]
    #[TestDox('Index page accepts optional filter query parameters without error')]
    public function indexAcceptsFilterQueryParameters(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/events', ['name' => 'Test', 'page' => 1]);

        $this->assertResponseIsSuccessful();
    }

    // -------------------------------------------------------------------------
    // new – GET
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('New page displays the event form when sport is selected')]
    public function newPageDisplaysForm(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $this->setCurrentSport($client);

        $client->request('GET', '/events/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    #[Test]
    #[TestDox('New endpoint returns 400 when no sport is selected')]
    public function newReturnsBadRequestWhenNoSportSelected(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/events/new', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // -------------------------------------------------------------------------
    // new – POST (tworzenie – competition nie-bracket)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the new form creates a non-bracket event and returns 201 JSON success')]
    public function submittingNewFormCreatesEvent(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $sport = $this->setCurrentSport($client);

        $competition = $this->createTestCompetition($sport, false);

        $crawler = $client->request('GET', '/events/new');
        $form    = $crawler->selectButton('Save')->form();

        $formData = [];
        foreach ($form->all() as $field) {
            $name = $field->getName();
            if (str_contains($name, '[name]')) {
                $formData[$name] = 'New Event';
            } elseif (str_contains($name, '[competition]')) {
                $formData[$name] = (string) $competition->getId();
            }
        }

        $client->submit($form, $formData);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var EventRepository $repository */
        $repository = static::getContainer()->get(EventRepository::class);
        $event      = $repository->findOneBy(['name' => 'New Event']);

        $this->assertNotNull($event);
        $this->assertEquals($competition->getId(), $event->getCompetition()?->getId());
        $this->assertTrue($event->isActive());
    }

    // -------------------------------------------------------------------------
    // new – POST (tworzenie – competition bracket z orderIndex)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the new form creates a bracket event with valid orderIndex and returns 201')]
    public function submittingNewFormCreatesBracketEventWithOrderIndex(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $sport = $this->setCurrentSport($client);

        $competition = $this->createTestCompetition($sport, true);

        // orderIndex jest disabled w HTML dla nowych eventów – DomCrawler nie wysyła
        // disabled pól, więc musimy użyć surowego POST. PRE_SUBMIT włączy pole gdy
        // wykryje bracket competition i przetworzy wartość z requestu.
        $client->request('POST', '/events/new', [
            'event' => [
                'name'        => 'Bracket Event',
                'competition' => (string) $competition->getId(),
                'orderIndex'  => '1',
                '_token'      => $this->getValidCsrfToken($client, '/events/new'),
            ],
        ]);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    // -------------------------------------------------------------------------
    // new – POST (walidacja: bracket bez orderIndex → !isValid())
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting new form for bracket competition without orderIndex returns 400')]
    public function submittingNewFormForBracketCompetitionWithoutOrderIndexReturns400(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $sport = $this->setCurrentSport($client);

        $competition = $this->createTestCompetition($sport, true);

        // POST_SUBMIT listener dodaje błąd gdy bracket competition i brak orderIndex
        $client->request('POST', '/events/new', [
            'event' => [
                'name'        => 'Bracket Event',
                'competition' => (string) $competition->getId(),
                'orderIndex'  => '',
                '_token'      => $this->getValidCsrfToken($client, '/events/new'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertArrayHasKey('errors', $data);
        $errors = $data['errors'];
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
    }

    #[Test]
    #[TestDox('Error message mentions orderIndex when bracket event is submitted without it')]
    public function bracketEventWithoutOrderIndexReturnsOrderIndexError(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $sport = $this->setCurrentSport($client);

        $competition = $this->createTestCompetition($sport, true);

        $client->request('POST', '/events/new', [
            'event' => [
                'name'        => 'Bracket Event',
                'competition' => (string) $competition->getId(),
                'orderIndex'  => '',
                '_token'      => $this->getValidCsrfToken($client, '/events/new'),
            ],
        ]);

        $data = $this->assertJsonSuccessResponse($client);
        $errors = $data['errors'];
        $this->assertIsArray($errors);

        $messages = array_column($errors, 'message');
        $found = false;
        foreach ($messages as $message) {
            if (str_contains((string) $message, 'Order index')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Expected an "Order index" error message');
    }

    #[Test]
    #[TestDox('Editing a bracket event to switch to non-bracket competition while keeping orderIndex returns 400')]
    public function editingBracketEventToNonBracketCompetitionWithOrderIndexReturns400(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport          = $this->createTestSport();
        $bracketComp    = $this->createTestCompetition($sport, true);
        $nonBracketComp = $this->createTestCompetition($sport, false);
        $event          = $this->createTestEvent($bracketComp, 1);

        $client->request('POST', sprintf('/events/%d/edit', $event->getId()), [
            'event' => [
                'name'        => 'Edited Event',
                'competition' => (string) $nonBracketComp->getId(),
                'orderIndex'  => '1',
                '_token'      => $this->getValidCsrfToken(
                    $client,
                    sprintf('/events/%d/edit', $event->getId())
                ),
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertArrayHasKey('errors', $data);
    }

    // -------------------------------------------------------------------------
    // edit – GET
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Edit page displays the event form prefilled with existing data')]
    public function editPageDisplaysForm(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport       = $this->createTestSport();
        $competition = $this->createTestCompetition($sport);
        $event       = $this->createTestEvent($competition);

        $client->request('GET', sprintf('/events/%d/edit', $event->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    // -------------------------------------------------------------------------
    // edit – POST (aktualizacja)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the edit form updates the event name in the database')]
    public function submittingEditFormUpdatesEvent(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport        = $this->createTestSport();
        $competition  = $this->createTestCompetition($sport);
        $event        = $this->createTestEvent($competition);
        $originalName = $event->getName();

        $crawler = $client->request('GET', sprintf('/events/%d/edit', $event->getId()));
        $form    = $crawler->selectButton('Save')->form();

        $formData = [];
        foreach ($form->all() as $field) {
            $name = $field->getName();
            if (str_contains($name, '[name]')) {
                $formData[$name] = 'Updated Event Name';
            }
        }

        $client->submit($form, $formData);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var EventRepository $repository */
        $repository = static::getContainer()->get(EventRepository::class);
        $updated    = $repository->find($event->getId());

        $this->assertNotNull($updated);
        $this->assertSame('Updated Event Name', $updated->getName());
        $this->assertNotEquals($originalName, $updated->getName());
    }

    #[Test]
    #[TestDox('Submitting edit form for bracket competition without orderIndex returns 400')]
    public function submittingEditFormForBracketCompetitionWithoutOrderIndexReturns400(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport       = $this->createTestSport();
        $competition = $this->createTestCompetition($sport, true);
        $event       = $this->createTestEvent($competition, 1);

        $client->request('POST', sprintf('/events/%d/edit', $event->getId()), [
            'event' => [
                'name'        => 'Updated Event',
                'competition' => (string) $competition->getId(),
                'orderIndex'  => '',
                '_token'      => $this->getValidCsrfToken($client, sprintf('/events/%d/edit', $event->getId())),
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
    #[TestDox('Delete request soft-deletes the event by setting it as inactive')]
    public function deleteSoftDeletesEvent(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport       = $this->createTestSport();
        $competition = $this->createTestCompetition($sport);
        $event       = $this->createTestEvent($competition);
        $eventId     = $event->getId();

        $crawler      = $client->request('GET', '/events');
        $deleteButton = $crawler->filter(sprintf('button[data-delete-url*="%d"]', $eventId));

        $this->assertGreaterThan(0, $deleteButton->count(), 'Delete button not found');

        $csrfToken = $deleteButton->attr('data-token');

        $client->request('POST', sprintf('/events/%d', $eventId), [
            '_token' => $csrfToken,
        ]);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var EventRepository $repository */
        $repository = static::getContainer()->get(EventRepository::class);
        $deleted    = $repository->find($eventId);

        $this->assertNotNull($deleted);
        $this->assertFalse($deleted->isActive());
    }

    #[Test]
    #[TestDox('Delete request with invalid CSRF token returns 403 and leaves event active')]
    public function deleteWithInvalidCsrfTokenReturnsForbidden(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport       = $this->createTestSport();
        $competition = $this->createTestCompetition($sport);
        $event       = $this->createTestEvent($competition);

        $client->request('POST', sprintf('/events/%d', $event->getId()), [
            '_token' => 'invalid_token',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var EventRepository $repository */
        $repository  = static::getContainer()->get(EventRepository::class);
        $stillActive = $repository->find($event->getId());

        $this->assertNotNull($stillActive);
        $this->assertTrue($stillActive->isActive());
    }

    // -------------------------------------------------------------------------
    // findByCompetition
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('findByCompetition returns list of events for a given competition ID')]
    public function findByCompetitionReturnsEventsForCompetition(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport = $this->setCurrentSport($client);
        $competition = $this->createTestCompetition($sport);
        $event       = $this->createTestEvent($competition);

        $client->request('GET', sprintf('/events/by-competition/%d', $competition->getId()));

        $this->assertResponseIsSuccessful();

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertIsArray($data);

        $ids = array_column($data, 'id');

        $this->assertContains($event->getId(), $ids);
    }

    #[Test]
    #[TestDox('Each entry in findByCompetition response contains "id" and "name" keys')]
    public function findByCompetitionResponseEntriesHaveCorrectStructure(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport       = $this->createTestSport();
        $competition = $this->createTestCompetition($sport);
        $this->createTestEvent($competition);

        $client->request('GET', sprintf('/events/by-competition/%d', $competition->getId()));

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertIsArray($data);

        foreach ($data as $entry) {
            $this->assertIsArray($entry);
            $this->assertArrayHasKey('id', $entry);
            $this->assertArrayHasKey('name', $entry);
        }
    }

    #[Test]
    #[TestDox('findByCompetition returns empty array when the event sport does not match the current sport')]
    public function findByCompetitionReturnsEmptyArrayWhenSportMismatch(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $this->setCurrentSport($client);                   // ustaw jakikolwiek sport w sesji
        $otherSport  = $this->createTestSport();           // inny sport
        $competition = $this->createTestCompetition($otherSport); // competition z innym sportem
        $event       = $this->createTestEvent($competition);      // event z tym competition

        // competitionId w URL to faktycznie ID Eventu
        $client->request('GET', sprintf('/events/by-competition/%d', $event->getId()));

        $this->assertResponseIsSuccessful();

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    #[Test]
    #[TestDox('findByCompetition returns empty array for a competition ID with no events')]
    public function findByCompetitionReturnsEmptyArrayForCompetitionWithNoEvents(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport       = $this->createTestSport();
        $competition = $this->createTestCompetition($sport);

        $client->request('GET', sprintf('/events/by-competition/%d', $competition->getId()));

        $this->assertResponseIsSuccessful();

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }
}
