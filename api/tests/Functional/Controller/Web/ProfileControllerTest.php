<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web;

use App\Entity\User;
use App\Tests\Trait\ControllerTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ProfileControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    private const string EDIT_URL = '/profile/edit';

    #[Test]
    #[TestDox('Profile form is prefilled with the current first and last name')]
    public function profileFormIsPrefilledWithCurrentName(): void
    {
        $client = self::createClient();
        $user   = $this->loginAsTestUser($client);

        $client->request('GET', self::EDIT_URL);

        $this->assertResponseIsSuccessful();
        $this->assertInputValueSame('profile[firstName]', $user->getFirstName());
        $this->assertInputValueSame('profile[lastName]', $user->getLastName());
    }

    #[Test]
    #[TestDox('Profile name is updated without surrounding whitespace')]
    public function profileNameIsUpdated(): void
    {
        $client = self::createClient();
        $user   = $this->loginAsTestUser($client);

        $this->submitProfileForm($client, ['firstName' => ' Łukasz ', 'lastName' => ' Śliwa']);

        $this->assertResponseIsSuccessful();
        $this->assertTrue($this->assertJsonSuccessResponse($client)['success']);

        $stored = $this->reloadUser($user);
        $this->assertSame('Łukasz', $stored->getFirstName());
        $this->assertSame('Śliwa', $stored->getLastName());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nameFieldProvider(): array
    {
        return [
            'first name' => ['firstName'],
            'last name'  => ['lastName'],
        ];
    }

    #[Test]
    #[DataProvider('nameFieldProvider')]
    #[TestDox('Profile with a blank $_dataName is rejected and the stored name is kept')]
    public function profileWithBlankNameIsRejected(string $field): void
    {
        $client   = self::createClient();
        $user     = $this->loginAsTestUser($client);
        $fullName = $user->getFullName();

        $this->submitProfileForm($client, ['firstName' => 'Jan', 'lastName' => 'Kowalski', $field => '   ']);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $errors = $this->assertJsonSuccessResponse($client)['errors'];
        $this->assertIsArray($errors);
        $this->assertContains($field, array_column($errors, 'field'));

        $this->assertSame($fullName, $this->reloadUser($user)->getFullName());
    }

    #[Test]
    #[TestDox('Anonymous visitor is redirected to the login page')]
    public function anonymousVisitorIsRedirectedToLogin(): void
    {
        $client = self::createClient();

        $client->request('GET', self::EDIT_URL);

        $this->assertResponseRedirects('/login');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Submits the real modal form (CSRF token included) with the given field values.
     *
     * @param array<string, string> $values keyed by field name, e.g. ['firstName' => 'Jan']
     */
    private function submitProfileForm(KernelBrowser $client, array $values): void
    {
        $form = $client->request('GET', self::EDIT_URL)->selectButton('Save')->form();

        $formData = [];
        foreach ($values as $field => $value) {
            $formData['profile[' . $field . ']'] = $value;
        }
        $client->submit($form, $formData);
    }

    private function reloadUser(User $user): User
    {
        $em = $this->getEntityManager();
        $em->clear();

        $stored = $em->find(User::class, $user->getId());
        $this->assertNotNull($stored);

        return $stored;
    }
}
