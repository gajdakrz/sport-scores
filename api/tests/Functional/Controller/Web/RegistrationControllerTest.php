<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web;

use App\Entity\User;
use App\Enum\Role;
use App\Repository\UserRepository;
use App\Tests\Trait\ControllerTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegistrationControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    // -------------------------------------------------------------------------
    // GET /register
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Register page is publicly accessible and renders the registration form')]
    public function registerPageIsPubliclyAccessible(): void
    {
        $client = self::createClient();

        $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    #[Test]
    #[TestDox('Register page contains name, email and password fields')]
    public function registerPageContainsRequiredFields(): void
    {
        $client = self::createClient();

        $client->request('GET', '/register');

        $this->assertSelectorExists('input[type="email"], input[name*="email"]');
        $this->assertSelectorExists('input[type="password"]');
        $this->assertSelectorExists('input[name="registration_form[firstName]"]');
        $this->assertSelectorExists('input[name="registration_form[lastName]"]');
    }

    // -------------------------------------------------------------------------
    // POST /register – sukces
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting valid registration data redirects to login page')]
    public function submittingValidFormRedirectsToLogin(): void
    {
        $client = self::createClient();

        // RegistrationFormType has no role field → Role::USER by default.
        // Registering a user requires an existing admin.
        $this->ensureAdminExists();

        $this->submitRegistrationForm($client);

        $this->assertResponseRedirects('/login');
    }

    #[Test]
    #[TestDox('Flash message "Account created!" is displayed after successful registration')]
    public function successfulRegistrationShowsFlashMessage(): void
    {
        $client = self::createClient();
        $this->ensureAdminExists();

        $this->submitRegistrationForm($client);
        $this->assertResponseRedirects('/login');

        $client->followRedirect();

        $this->assertSelectorTextContains('[class*="flash"], .alert, .notice', 'Account created!');
    }

    #[Test]
    #[TestDox('Registered user is stored with the first and last name from the form')]
    public function userIsCreatedWithFirstAndLastName(): void
    {
        $client = self::createClient();
        $this->ensureAdminExists();

        $email = $this->submitRegistrationForm($client, ['firstName' => ' Łukasz ', 'lastName' => 'Śliwa']);

        $this->assertResponseRedirects('/login');
        $user = $this->findUserByEmail($email);
        $this->assertNotNull($user);
        $this->assertSame('Łukasz', $user->getFirstName());
        $this->assertSame('Śliwa', $user->getLastName());
    }

    // -------------------------------------------------------------------------
    // POST /register – invalid form (!isValid())
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the form with invalid CSRF token re-renders the form without redirect')]
    public function submittingFormWithInvalidCsrfTokenRendersFormAgain(): void
    {
        $client = self::createClient();

        // The only way to !isValid() apart from DTO validation is an invalid CSRF token.
        $client->request('POST', '/register', [
            'registration_form' => [
                'firstName'     => 'Jan',
                'lastName'      => 'Kowalski',
                'email'         => 'test@example.com',
                'plainPassword' => 'Secret123!',
                '_token'        => 'invalid_csrf_token',
            ],
        ]);

        // Invalid form → the controller renders the page again (no redirect)
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('form');
    }

    /**
     * @return array<string, array{array<string, string>}>
     */
    public static function invalidRegistrationDataProvider(): array
    {
        return [
            'empty email'        => [['email' => '']],
            'too short password' => [['plainPassword' => '123']],
            'blank first name'   => [['firstName' => '  ']],
            'blank last name'    => [['lastName' => '']],
        ];
    }

    /**
     * @param array<string, string> $overrides
     */
    #[Test]
    #[DataProvider('invalidRegistrationDataProvider')]
    #[TestDox('Registration with $_dataName is rejected and the form is re-rendered')]
    public function registrationWithInvalidDataIsRejected(array $overrides): void
    {
        $client = self::createClient();
        $this->ensureAdminExists();

        $email = $this->submitRegistrationForm($client, $overrides);

        // DTO validation → isValid()=false → page re-rendered, nothing stored
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('form');
        $this->assertNull($this->findUserByEmail($email));
    }

    // -------------------------------------------------------------------------
    // Access for logged-in users
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Register page is accessible even when user is already authenticated')]
    public function registerPageIsAccessibleWhenAuthenticated(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Fills the real registration form (CSRF token included) with valid data, overridden by $overrides.
     *
     * @param array<string, string> $overrides keyed by field name, e.g. ['firstName' => '']
     * @return string the submitted email
     */
    private function submitRegistrationForm(KernelBrowser $client, array $overrides = []): string
    {
        $data = [
            'firstName'     => 'Jan',
            'lastName'      => 'Kowalski',
            'email'         => 'user_' . uniqid() . '@example.com',
            'plainPassword' => 'Secret123!',
            ...$overrides,
        ];

        $form = $client->request('GET', '/register')->selectButton('Register')->form();

        $formData = [];
        foreach ($data as $field => $value) {
            $formData['registration_form[' . $field . ']'] = $value;
        }
        $client->submit($form, $formData);

        return $data['email'];
    }

    private function findUserByEmail(string $email): ?User
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        return $userRepository->findOneBy(['email' => $email]);
    }

    /**
     * Rejestracja usera wymaga istnienia admina (RegistrationUserService::register()).
     * POST /api/v1/user no longer accepts a role from the client (mass-assignment fix), so
     * the admin is created directly through the EntityManager, the same way app:create-user does it.
     */
    private function ensureAdminExists(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        if ($userRepository->isAdminExists()) {
            return;
        }

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $admin = new User();
        $admin->setEmail('admin_setup_' . uniqid() . '@example.com');
        $admin->setRoles([Role::ADMIN->value]);
        $admin->setPassword($passwordHasher->hashPassword($admin, 'Secret123!'));

        $em->persist($admin);
        $em->flush();
    }
}
