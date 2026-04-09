<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\MemberPosition;
use App\Repository\MemberPositionRepository;
use App\Tests\Trait\ControllerTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MemberPositionControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    public function testIndex(): void
    {
        $client = MemberPositionControllerTest::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/member-positions');

        $this->assertResponseIsSuccessful();
    }

    public function testNewDisplaysForm(): void
    {
        $client = MemberPositionControllerTest::createClient();
        $this->loginAsTestUser($client);
        $this->setCurrentSport($client);

        $client->request('GET', '/member-positions/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testNewCreatesMemberPosition(): void
    {
        $client = MemberPositionControllerTest::createClient();
        $this->loginAsTestUser($client);
        $sport = $this->setCurrentSport($client);

        $crawler = $client->request('GET', '/member-positions/new');

        $form = $crawler->selectButton('Save')->form();

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

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);

        /** @var MemberPositionRepository $repository */
        $repository = MemberPositionControllerTest::getContainer()->get(MemberPositionRepository::class);
        $position = $repository->findOneBy(['name' => 'Test Position']);

        $this->assertNotNull($position);
        $this->assertEquals($sport->getId(), $position->getSport()->getId());
        $this->assertTrue($position->isActive());
    }

    public function testNewRedirectsWhenNoSportSelected(): void
    {
        $client = MemberPositionControllerTest::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/member-positions/new');

        $this->assertResponseRedirects('/member-positions');
        $client->followRedirect();

        $this->assertSelectorExists('.alert, .flash-message');
    }

    public function testEditDisplaysForm(): void
    {
        $client = MemberPositionControllerTest::createClient();
        $this->loginAsTestUser($client);

        $position = $this->createTestMemberPosition();

        $client->request('GET', sprintf('/member-positions/%d/edit', $position->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testEditUpdatesMemberPosition(): void
    {
        $client = MemberPositionControllerTest::createClient();
        $this->loginAsTestUser($client);

        $position = $this->createTestMemberPosition();
        $originalName = $position->getName();

        $crawler = $client->request('GET', sprintf('/member-positions/%d/edit', $position->getId()));

        $form = $crawler->selectButton('Save')->form();

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

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);

        /** @var EntityManagerInterface $em */
        $em = MemberPositionControllerTest::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        /** @var MemberPositionRepository $repository */
        $repository = MemberPositionControllerTest::getContainer()->get(MemberPositionRepository::class);
        $updatedPosition = $repository->find($position->getId());

        $this->assertNotNull($updatedPosition);
        $this->assertEquals('Updated Position Name', $updatedPosition->getName());
        $this->assertNotEquals($originalName, $updatedPosition->getName());
    }

    public function testDeleteSoftMemberPosition(): void
    {
        $client = MemberPositionControllerTest::createClient();
        $this->loginAsTestUser($client);

        $position = $this->createTestMemberPosition();
        $positionId = $position->getId();

        $crawler = $client->request('GET', '/member-positions');
        $deleteButton = $crawler->filter(sprintf('button[data-delete-url*="%d"]', $positionId));

        $this->assertGreaterThan(0, $deleteButton->count(), 'Delete button not found');

        $csrfToken = $deleteButton->attr('data-token');

        $client->request('POST', sprintf('/member-positions/%d', $positionId), [
            '_token' => $csrfToken
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);

        /** @var EntityManagerInterface $em */
        $em = MemberPositionControllerTest::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        /** @var MemberPositionRepository $repository */
        $repository = MemberPositionControllerTest::getContainer()->get(MemberPositionRepository::class);
        $deletedPosition = $repository->find($positionId);

        $this->assertNotNull($deletedPosition);
        $this->assertFalse($deletedPosition->isActive());
    }

    public function testDeleteRequiresValidCsrfToken(): void
    {
        $client = MemberPositionControllerTest::createClient();
        $this->loginAsTestUser($client);

        $position = $this->createTestMemberPosition();

        $client->request('POST', sprintf('/member-positions/%d', $position->getId()), [
            '_token' => 'invalid_token'
        ]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertJson($client->getResponse()->getContent());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Invalid CSRF token', $data['error']);

        /** @var EntityManagerInterface $em */
        $em = MemberPositionControllerTest::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        /** @var MemberPositionRepository $repository */
        $repository = MemberPositionControllerTest::getContainer()->get(MemberPositionRepository::class);
        $stillActivePosition = $repository->find($position->getId());

        $this->assertNotNull($stillActivePosition);
        $this->assertTrue($stillActivePosition->isActive());
    }

    public function testIndexRequiresAuthentication(): void
    {
        $client = MemberPositionControllerTest::createClient();

        $client->request('GET', '/member-positions');

        $this->assertResponseRedirects();
    }

    private function createTestMemberPosition(): MemberPosition
    {
        /** @var EntityManagerInterface $em */
        $em = MemberPositionControllerTest::getContainer()->get(EntityManagerInterface::class);
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
