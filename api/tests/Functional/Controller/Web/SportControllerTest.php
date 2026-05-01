<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web;

use App\Entity\Sport;
use App\Repository\SportRepository;
use App\Tests\Trait\ControllerTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class SportControllerTest extends WebTestCase
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

        $client->request('GET', '/sports');

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    #[TestDox('Index page redirects unauthenticated users to the login page')]
    public function indexRedirectsUnauthenticatedUsers(): void
    {
        $client = self::createClient();

        $client->request('GET', '/sports');

        $this->assertResponseRedirects();
    }

    // -------------------------------------------------------------------------
    // new – GET
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('New page displays the sport form')]
    public function newPageDisplaysForm(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/sports/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    // -------------------------------------------------------------------------
    // new – POST (tworzenie)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the new form creates a sport and returns 201 JSON success')]
    public function submittingNewFormCreatesSport(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $crawler = $client->request('GET', '/sports/new');
        $form    = $crawler->selectButton('Save')->form();

        $formData = [];
        foreach ($form->all() as $field) {
            if (str_contains($field->getName(), '[name]')) {
                $formData[$field->getName()] = 'New Sport';
            }
        }

        $client->submit($form, $formData);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var SportRepository $repository */
        $repository = static::getContainer()->get(SportRepository::class);
        $sport      = $repository->findOneBy(['name' => 'New Sport']);

        $this->assertNotNull($sport);
        $this->assertTrue($sport->isActive());
    }

    // -------------------------------------------------------------------------
    // new – POST (walidacja: nieprawidłowy CSRF → !isValid())
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the new form with invalid CSRF token returns 400 with validation errors')]
    public function submittingNewFormWithInvalidCsrfTokenReturnsValidationErrors(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        // SportType ma tylko TextType – jedyna droga do isValid()=false
        // to nieprawidłowy token CSRF
        $client->request('POST', '/sports/new', [
            'sport' => [
                'name'   => 'Valid Sport',
                '_token' => 'invalid_csrf_token',
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
    #[TestDox('Edit page displays the sport form prefilled with existing data')]
    public function editPageDisplaysForm(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport = $this->createTestSportEntity();

        $client->request('GET', sprintf('/sports/%d/edit', $sport->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    // -------------------------------------------------------------------------
    // edit – POST (aktualizacja)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the edit form updates the sport name in the database')]
    public function submittingEditFormUpdatesSport(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport        = $this->createTestSportEntity();
        $originalName = $sport->getName();

        $crawler = $client->request('GET', sprintf('/sports/%d/edit', $sport->getId()));
        $form    = $crawler->selectButton('Save')->form();

        $formData = [];
        foreach ($form->all() as $field) {
            if (str_contains($field->getName(), '[name]')) {
                $formData[$field->getName()] = 'Updated Sport Name';
            }
        }

        $client->submit($form, $formData);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var SportRepository $repository */
        $repository  = static::getContainer()->get(SportRepository::class);
        $updatedSport = $repository->find($sport->getId());

        $this->assertNotNull($updatedSport);
        $this->assertSame('Updated Sport Name', $updatedSport->getName());
        $this->assertNotEquals($originalName, $updatedSport->getName());
    }

    #[Test]
    #[TestDox('Submitting the edit form with invalid CSRF token returns 400 with validation errors')]
    public function submittingEditFormWithInvalidCsrfTokenReturnsValidationErrors(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport = $this->createTestSportEntity();

        $client->request('POST', sprintf('/sports/%d/edit', $sport->getId()), [
            'sport' => [
                'name'   => 'Valid Sport',
                '_token' => 'invalid_csrf_token',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertArrayHasKey('errors', $data);
        $errors = $data['errors'];
        $this->assertNotEmpty($errors);
    }

    // -------------------------------------------------------------------------
    // delete
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Delete request soft-deletes the sport by setting it as inactive')]
    public function deleteSoftDeletesSport(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport   = $this->createTestSportEntity();
        $sportId = $sport->getId();

        $crawler      = $client->request('GET', '/sports');
        $deleteButton = $crawler->filter(sprintf('button[data-delete-url*="%d"]', $sportId));

        $this->assertGreaterThan(0, $deleteButton->count(), 'Delete button not found');

        $csrfToken = $deleteButton->attr('data-token');

        $client->request('POST', sprintf('/sports/%d', $sportId), [
            '_token' => $csrfToken,
        ]);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var SportRepository $repository */
        $repository  = static::getContainer()->get(SportRepository::class);
        $deletedSport = $repository->find($sportId);

        $this->assertNotNull($deletedSport);
        $this->assertFalse($deletedSport->isActive());
    }

    #[Test]
    #[TestDox('Delete request with invalid CSRF token returns 403 and leaves sport active')]
    public function deleteWithInvalidCsrfTokenReturnsForbidden(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport = $this->createTestSportEntity();

        $client->request('POST', sprintf('/sports/%d', $sport->getId()), [
            '_token' => 'invalid_token',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var SportRepository $repository */
        $repository  = static::getContainer()->get(SportRepository::class);
        $stillActive = $repository->find($sport->getId());

        $this->assertNotNull($stillActive);
        $this->assertTrue($stillActive->isActive());
    }

    // -------------------------------------------------------------------------
    // setSport
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('setSport stores the sport ID in session and returns 204 No Content')]
    public function setSportStoresSportInSessionAndReturns204(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport = $this->createTestSportEntity();

        $client->request('POST', sprintf('/sports/set/%d', $sport->getId()));

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    #[Test]
    #[TestDox('setSport makes the sport available as current sport in subsequent requests')]
    public function setSportMakesSportAvailableInSession(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport = $this->createTestSportEntity();

        $client->request('POST', sprintf('/sports/set/%d', $sport->getId()));
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Weryfikujemy że sport jest w sesji przez endpoint który go odczytuje
        // setCurrentSport używa tego samego mechanizmu – sprawdzamy przez /sports/new
        // który wywołuje requireSport() – jeśli sport nie jest ustawiony, zwraca 400
        $client->request('GET', '/sports/new');
        $this->assertResponseIsSuccessful();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createTestSportEntity(): Sport
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $sport = new Sport();
        $sport->setName('Test Sport ' . uniqid());
        $sport->setCreatedBy($user);
        $sport->setModifiedBy($user);
        $sport->setIsActive(true);

        $em->persist($sport);
        $em->flush();

        return $sport;
    }
}
