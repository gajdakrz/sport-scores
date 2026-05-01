<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web;

use App\Entity\MemberPosition;
use App\Repository\MemberPositionRepository;
use App\Tests\Trait\ControllerTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class MemberPositionControllerTest extends WebTestCase
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

        $client->request('GET', '/member-positions');

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    #[TestDox('Index page redirects unauthenticated users to the login page')]
    public function indexRedirectsUnauthenticatedUsers(): void
    {
        $client = self::createClient();

        $client->request('GET', '/member-positions');

        $this->assertResponseRedirects();
    }

    // -------------------------------------------------------------------------
    // new – GET
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('New page displays the member position form when sport is selected')]
    public function newPageDisplaysForm(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $this->setCurrentSport($client);

        $client->request('GET', '/member-positions/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    #[Test]
    #[TestDox('New endpoint returns 400 when no sport is selected')]
    public function newReturnsBadRequestWhenNoSportSelected(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/member-positions/new', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertResponseStatusCodeSame(400);
        $data = $this->assertJsonSuccessResponse($client);
        $this->assertArrayHasKey('errors', $data);
        $errors = $data['errors'];
        $this->assertIsArray($errors);
        $this->assertIsArray($errors[0]);
        $this->assertSame('Sport not selected', $errors[0]['message']);
    }

    // -------------------------------------------------------------------------
    // new – POST (tworzenie)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the new form creates a member position and returns 201 JSON success')]
    public function submittingNewFormCreatesMemberPosition(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $sport = $this->setCurrentSport($client);

        $crawler = $client->request('GET', '/member-positions/new');
        $form    = $crawler->selectButton('Save')->form();

        $formData = [];
        foreach ($form->all() as $field) {
            $fieldName = $field->getName();
            if (str_contains($fieldName, '[name]')) {
                $formData[$fieldName] = 'Test Position';
            } elseif (str_contains($fieldName, '[abbreviation]')) {
                $formData[$fieldName] = 'TP';
            }
        }

        $client->submit($form, $formData);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        /** @var MemberPositionRepository $repository */
        $repository = static::getContainer()->get(MemberPositionRepository::class);
        $position   = $repository->findOneBy(['name' => 'Test Position']);

        $this->assertNotNull($position);
        $this->assertEquals($sport->getId(), $position->getSport()?->getId());
        $this->assertTrue($position->isActive());
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
        $this->setCurrentSport($client);

        // MemberPositionType ma tylko TextType – jedyna droga do isValid()=false
        // to nieprawidłowy token CSRF. Surowy POST omija walidację DomCrawlera.
        $client->request('POST', '/member-positions/new', [
            'member_position' => [
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
    #[TestDox('Edit page displays the member position form prefilled with existing data')]
    public function editPageDisplaysForm(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $position = $this->createTestMemberPosition();

        $client->request('GET', sprintf('/member-positions/%d/edit', $position->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    // -------------------------------------------------------------------------
    // edit – POST (aktualizacja)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the edit form updates the member position name in the database')]
    public function submittingEditFormUpdatesMemberPosition(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $position     = $this->createTestMemberPosition();
        $originalName = $position->getName();

        $crawler = $client->request('GET', sprintf('/member-positions/%d/edit', $position->getId()));
        $form    = $crawler->selectButton('Save')->form();

        $formData = [];
        foreach ($form->all() as $field) {
            $fieldName = $field->getName();
            if (str_contains($fieldName, '[name]')) {
                $formData[$fieldName] = 'Updated Position Name';
            } elseif (str_contains($fieldName, '[abbreviation]')) {
                $formData[$fieldName] = 'UPN';
            }
        }

        $client->submit($form, $formData);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var MemberPositionRepository $repository */
        $repository     = static::getContainer()->get(MemberPositionRepository::class);
        $updatedPosition = $repository->find($position->getId());

        $this->assertNotNull($updatedPosition);
        $this->assertEquals('Updated Position Name', $updatedPosition->getName());
        $this->assertNotEquals($originalName, $updatedPosition->getName());
    }

    // -------------------------------------------------------------------------
    // edit – POST (walidacja: nieprawidłowy CSRF → !isValid())
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the edit form with invalid CSRF token returns 400 with validation errors')]
    public function submittingEditFormWithInvalidCsrfTokenReturnsValidationErrors(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $position = $this->createTestMemberPosition();

        $client->request('POST', sprintf('/member-positions/%d/edit', $position->getId()), [
            'member_position' => [
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
    #[TestDox('Delete request soft-deletes the member position by setting it as inactive')]
    public function deleteSoftDeletesMemberPosition(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $position   = $this->createTestMemberPosition();
        $positionId = $position->getId();

        $crawler      = $client->request('GET', '/member-positions');
        $deleteButton = $crawler->filter(sprintf('button[data-delete-url*="%d"]', $positionId));

        $this->assertGreaterThan(0, $deleteButton->count(), 'Delete button not found');

        $csrfToken = $deleteButton->attr('data-token');

        $client->request('POST', sprintf('/member-positions/%d', $positionId), [
            '_token' => $csrfToken,
        ]);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var MemberPositionRepository $repository */
        $repository     = static::getContainer()->get(MemberPositionRepository::class);
        $deletedPosition = $repository->find($positionId);

        $this->assertNotNull($deletedPosition);
        $this->assertFalse($deletedPosition->isActive());
    }

    #[Test]
    #[TestDox('Delete request with invalid CSRF token returns 403 and leaves position active')]
    public function deleteWithInvalidCsrfTokenReturnsForbidden(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $position = $this->createTestMemberPosition();

        $client->request('POST', sprintf('/member-positions/%d', $position->getId()), [
            '_token' => 'invalid_token',
        ]);

        $this->assertResponseStatusCodeSame(403);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertFalse($data['success']);
        $this->assertEquals('Invalid CSRF token', $data['error']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var MemberPositionRepository $repository */
        $repository      = static::getContainer()->get(MemberPositionRepository::class);
        $stillActive     = $repository->find($position->getId());

        $this->assertNotNull($stillActive);
        $this->assertTrue($stillActive->isActive());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createTestMemberPosition(): MemberPosition
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();
        $sport = $this->createTestSport();

        $position = new MemberPosition();
        $position->setName('Test Position ' . uniqid());
        $position->setSport($sport);
        $position->setCreatedBy($user);
        $position->setModifiedBy($user);
        $position->setIsActive(true);

        $em->persist($position);
        $em->flush();

        return $position;
    }
}
