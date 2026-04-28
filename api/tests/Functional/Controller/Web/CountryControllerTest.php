<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web;

use App\Entity\Country;
use App\Repository\CountryRepository;
use App\Tests\Trait\ControllerTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CountryControllerTest extends WebTestCase
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

        $client->request('GET', '/countries');

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    #[TestDox('Index page redirects unauthenticated users to the login page')]
    public function indexRedirectsUnauthenticatedUsers(): void
    {
        $client = self::createClient();

        $client->request('GET', '/countries');

        $this->assertResponseRedirects();
    }

    // -------------------------------------------------------------------------
    // new – GET
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('New page displays the country form')]
    public function newPageDisplaysForm(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/countries/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    // -------------------------------------------------------------------------
    // new – POST (tworzenie)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the new form creates a country and returns 201 JSON success')]
    public function submittingNewFormCreatesCountry(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $crawler = $client->request('GET', '/countries/new');
        $form    = $crawler->selectButton('Save')->form();

        $formData = [];
        foreach ($form->all() as $field) {
            if (str_contains($field->getName(), '[name]')) {
                $formData[$field->getName()] = 'New Country';
            }
        }

        $client->submit($form, $formData);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var CountryRepository $repository */
        $repository = static::getContainer()->get(CountryRepository::class);
        $country    = $repository->findOneBy(['name' => 'New Country']);

        $this->assertNotNull($country);
        $this->assertTrue($country->isActive());
    }

    #[Test]
    #[TestDox('Submitting the new form with invalid CSRF token returns 400 with validation errors')]
    public function submittingNewFormWithInvalidCsrfTokenReturnsValidationErrors(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        // CountryType ma tylko TextType – żadnych enumów ani constraints, więc
        // jedyną drogą do form->isValid() = false jest nieprawidłowy token CSRF.
        // Surowy POST omija walidację DomCrawlera i wysyła błędny _token.
        $client->request('POST', '/countries/new', [
            'country' => [
                'name'   => 'Valid Name',
                '_token' => 'invalid_csrf_token',
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
    #[TestDox('Edit page displays the country form prefilled with existing data')]
    public function editPageDisplaysForm(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $country = $this->createTestCountry();

        $client->request('GET', sprintf('/countries/%d/edit', $country->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    // -------------------------------------------------------------------------
    // edit – POST (aktualizacja)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the edit form updates the country name in the database')]
    public function submittingEditFormUpdatesCountry(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $country      = $this->createTestCountry();
        $originalName = $country->getName();

        $crawler = $client->request('GET', sprintf('/countries/%d/edit', $country->getId()));
        $form    = $crawler->selectButton('Save')->form();

        $formData = [];
        foreach ($form->all() as $field) {
            if (str_contains($field->getName(), '[name]')) {
                $formData[$field->getName()] = 'Updated Country Name';
            }
        }

        $client->submit($form, $formData);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var CountryRepository $repository */
        $repository = static::getContainer()->get(CountryRepository::class);
        $updated    = $repository->find($country->getId());

        $this->assertNotNull($updated);
        $this->assertSame('Updated Country Name', $updated->getName());
        $this->assertNotEquals($originalName, $updated->getName());
    }

    #[Test]
    #[TestDox('Submitting the edit form with invalid CSRF token returns 400 with validation errors')]
    public function submittingEditFormWithInvalidCsrfTokenReturnsValidationErrors(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $country = $this->createTestCountry();

        $client->request('POST', sprintf('/countries/%d/edit', $country->getId()), [
            'country' => [
                'name'   => 'Valid Name',
                '_token' => 'invalid_csrf_token',
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
    #[TestDox('Delete request soft-deletes the country by setting it as inactive')]
    public function deleteSoftDeletesCountry(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $country   = $this->createTestCountry();
        $countryId = $country->getId();

        $crawler      = $client->request('GET', '/countries');
        $deleteButton = $crawler->filter(sprintf('button[data-delete-url*="%d"]', $countryId));

        $this->assertGreaterThan(0, $deleteButton->count(), 'Delete button not found');

        $csrfToken = $deleteButton->attr('data-token');

        $client->request('POST', sprintf('/countries/%d', $countryId), [
            '_token' => $csrfToken,
        ]);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var CountryRepository $repository */
        $repository = static::getContainer()->get(CountryRepository::class);
        $deleted    = $repository->find($countryId);

        $this->assertNotNull($deleted);
        $this->assertFalse($deleted->isActive());
    }

    #[Test]
    #[TestDox('Delete request with invalid CSRF token returns 403 and leaves country active')]
    public function deleteWithInvalidCsrfTokenReturnsForbidden(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $country = $this->createTestCountry();

        $client->request('POST', sprintf('/countries/%d', $country->getId()), [
            '_token' => 'invalid_token',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertFalse($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var CountryRepository $repository */
        $repository  = static::getContainer()->get(CountryRepository::class);
        $stillActive = $repository->find($country->getId());

        $this->assertNotNull($stillActive);
        $this->assertTrue($stillActive->isActive());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function getEntityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        return $em;
    }

    private function createTestCountry(): Country
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $country = new Country();
        $country->setName('Test Country ' . uniqid());
        $country->setCreatedBy($user);
        $country->setModifiedBy($user);
        $country->setIsActive(true);

        $em->persist($country);
        $em->flush();

        return $country;
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
}
