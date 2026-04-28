<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web;

use App\Entity\Season;
use App\Repository\SeasonRepository;
use App\Tests\Trait\ControllerTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class SeasonControllerTest extends WebTestCase
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

    private function createTestSeason(int $startYear = 2020, int $endYear = 2021): Season
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $season = new Season();
        $season->setStartYear($startYear);
        $season->setEndYear($endYear);
        $season->setCreatedBy($user);
        $season->setModifiedBy($user);
        $season->setIsActive(true);

        $em->persist($season);
        $em->flush();

        return $season;
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

        $client->request('GET', '/seasons');

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    #[TestDox('Index page redirects unauthenticated users to the login page')]
    public function indexRedirectsUnauthenticatedUsers(): void
    {
        $client = self::createClient();

        $client->request('GET', '/seasons');

        $this->assertResponseRedirects();
    }

    // -------------------------------------------------------------------------
    // new – GET
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('New page displays the season form')]
    public function newPageDisplaysForm(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/seasons/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    // -------------------------------------------------------------------------
    // new – POST (tworzenie)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the new form creates a season and returns 201 JSON success')]
    public function submittingNewFormCreatesSeason(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $crawler = $client->request('GET', '/seasons/new');
        $form    = $crawler->selectButton('Save')->form();

        $currentYear = (int) date('Y');

        $formData = [];
        foreach ($form->all() as $field) {
            $name = $field->getName();
            if (str_contains($name, '[startYear]')) {
                $formData[$name] = (string) ($currentYear - 2);
            } elseif (str_contains($name, '[endYear]')) {
                $formData[$name] = (string) ($currentYear - 1);
            }
        }

        $client->submit($form, $formData);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var SeasonRepository $repository */
        $repository = static::getContainer()->get(SeasonRepository::class);
        $season     = $repository->findOneBy(['startYear' => $currentYear - 2, 'endYear' => $currentYear - 1]);

        $this->assertNotNull($season);
        $this->assertTrue($season->isActive());
    }

    // -------------------------------------------------------------------------
    // new – POST (walidacja: startYear > endYear → SeasonYearRange → !isValid())
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the new form with startYear greater than endYear returns 400')]
    public function submittingNewFormWithStartYearGreaterThanEndYearReturns400(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $currentYear = (int) date('Y');

        // Obie wartości są w zakresie choices, ale startYear > endYear narusza SeasonYearRange
        // → form->isValid()=false → throwFormErrors() → 400
        $client->request('POST', '/seasons/new', [
            'season' => [
                'startYear' => (string) $currentYear,
                'endYear'   => (string) ($currentYear - 2),
                '_token'    => $this->getValidCsrfTokenFromForm($client, '/seasons/new'),
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
    #[TestDox('Edit page displays the season form prefilled with existing data')]
    public function editPageDisplaysForm(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $season = $this->createTestSeason();

        $client->request('GET', sprintf('/seasons/%d/edit', $season->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    // -------------------------------------------------------------------------
    // edit – POST (aktualizacja)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the edit form updates the season years in the database')]
    public function submittingEditFormUpdatesSeason(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $season = $this->createTestSeason(2018, 2019);

        $crawler = $client->request('GET', sprintf('/seasons/%d/edit', $season->getId()));
        $form    = $crawler->selectButton('Save')->form();

        $currentYear = (int) date('Y');

        $formData = [];
        foreach ($form->all() as $field) {
            $name = $field->getName();
            if (str_contains($name, '[startYear]')) {
                $formData[$name] = (string) ($currentYear - 3);
            } elseif (str_contains($name, '[endYear]')) {
                $formData[$name] = (string) ($currentYear - 2);
            }
        }

        $client->submit($form, $formData);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var SeasonRepository $repository */
        $repository = static::getContainer()->get(SeasonRepository::class);
        $updated    = $repository->find($season->getId());

        $this->assertNotNull($updated);
        $this->assertSame($currentYear - 3, $updated->getStartYear());
        $this->assertSame($currentYear - 2, $updated->getEndYear());
    }

    #[Test]
    #[TestDox('Submitting the edit form with startYear greater than endYear returns 400')]
    public function submittingEditFormWithInvalidYearRangeReturns400(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $season      = $this->createTestSeason();
        $currentYear = (int) date('Y');

        $client->request('POST', sprintf('/seasons/%d/edit', $season->getId()), [
            'season' => [
                'startYear' => (string) $currentYear,
                'endYear'   => (string) ($currentYear - 2),
                '_token'    => $this->getValidCsrfTokenFromForm($client, sprintf('/seasons/%d/edit', $season->getId())),
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
    #[TestDox('Delete request soft-deletes the season by setting it as inactive')]
    public function deleteSoftDeletesSeason(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $season   = $this->createTestSeason();
        $seasonId = $season->getId();

        $crawler      = $client->request('GET', '/seasons');
        $deleteButton = $crawler->filter(sprintf('button[data-delete-url*="%d"]', $seasonId));

        $this->assertGreaterThan(0, $deleteButton->count(), 'Delete button not found');

        $csrfToken = $deleteButton->attr('data-token');

        $client->request('POST', sprintf('/seasons/%d', $seasonId), [
            '_token' => $csrfToken,
        ]);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var SeasonRepository $repository */
        $repository    = static::getContainer()->get(SeasonRepository::class);
        $deletedSeason = $repository->find($seasonId);

        $this->assertNotNull($deletedSeason);
        $this->assertFalse($deletedSeason->isActive());
    }

    #[Test]
    #[TestDox('Delete request with invalid CSRF token returns 403 and leaves season active')]
    public function deleteWithInvalidCsrfTokenReturnsForbidden(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $season = $this->createTestSeason();

        $client->request('POST', sprintf('/seasons/%d', $season->getId()), [
            '_token' => 'invalid_token',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var SeasonRepository $repository */
        $repository  = static::getContainer()->get(SeasonRepository::class);
        $stillActive = $repository->find($season->getId());

        $this->assertNotNull($stillActive);
        $this->assertTrue($stillActive->isActive());
    }

    // -------------------------------------------------------------------------
    // Helper prywatny
    // -------------------------------------------------------------------------

    private function getValidCsrfTokenFromForm(KernelBrowser $client, string $url): string
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
