<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web;

use App\Entity\User;
use App\Enum\Role;
use App\Repository\UserRepository;
use App\Tests\Trait\ControllerTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
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
    #[TestDox('Register page contains email and password fields')]
    public function registerPageContainsRequiredFields(): void
    {
        $client = self::createClient();

        $client->request('GET', '/register');

        $this->assertSelectorExists('input[type="email"], input[name*="email"]');
        $this->assertSelectorExists('input[type="password"]');
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

        $crawler = $client->request('GET', '/register');
        $form    = $crawler->selectButton('Register')->form();

        $formData = [];
        foreach ($form->all() as $field) {
            $name = $field->getName();
            if (str_contains($name, '[email]')) {
                $formData[$name] = 'user_' . uniqid() . '@example.com';
            } elseif (str_contains($name, '[plainPassword]')) {
                $formData[$name] = 'Secret123!';
            }
        }

        $client->submit($form, $formData);

        $this->assertResponseRedirects('/login');
    }

    #[Test]
    #[TestDox('Flash message "Account created!" is displayed after successful registration')]
    public function successfulRegistrationShowsFlashMessage(): void
    {
        $client = self::createClient();
        $this->ensureAdminExists();

        $crawler = $client->request('GET', '/register');
        $form    = $crawler->selectButton('Register')->form();

        $formData = [];
        foreach ($form->all() as $field) {
            $name = $field->getName();
            if (str_contains($name, '[email]')) {
                $formData[$name] = 'user_' . uniqid() . '@example.com';
            } elseif (str_contains($name, '[plainPassword]')) {
                $formData[$name] = 'Secret123!';
            }
        }

        $client->submit($form, $formData);
        $this->assertResponseRedirects('/login');

        $client->followRedirect();

        $this->assertSelectorTextContains('[class*="flash"], .alert, .notice', 'Account created!');
    }

    // -------------------------------------------------------------------------
    // POST /register – invalid form (!isValid())
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the form with invalid CSRF token re-renders the form without redirect')]
    public function submittingFormWithInvalidCsrfTokenRendersFormAgain(): void
    {
        $client = self::createClient();

        // RegistrationFormType has only EmailType and PasswordType – no enums.
        // The only way to !isValid() (apart from DTO validation) is an invalid CSRF token.
        $client->request('POST', '/register', [
            'registration_form' => [
                'email'         => 'test@example.com',
                'plainPassword' => 'Secret123!',
                '_token'        => 'invalid_csrf_token',
            ],
        ]);

        // Invalid form → the controller renders the page again (no redirect)
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('form');
    }

    #[Test]
    #[TestDox('Submitting the form with an empty email re-renders the registration form')]
    public function submittingFormWithEmptyEmailRendersFormAgain(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/register');
        $form    = $crawler->selectButton('Register')->form();

        $formData = [];
        foreach ($form->all() as $field) {
            $name = $field->getName();
            if (str_contains($name, '[email]')) {
                $formData[$name] = '';
            } elseif (str_contains($name, '[plainPassword]')) {
                $formData[$name] = 'Secret123!';
            }
        }

        $client->submit($form, $formData);

        // DTO validation (NotBlank on email) → isValid()=false → page re-rendered
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('form');
        $this->assertResponseIsSuccessful(); // no redirect = 200
    }

    #[Test]
    #[TestDox('Submitting the form with a password shorter than 6 characters re-renders the registration form')]
    public function submittingFormWithTooShortPasswordRendersFormAgain(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/register');
        $form    = $crawler->selectButton('Register')->form();

        $formData = [];
        foreach ($form->all() as $field) {
            $name = $field->getName();
            if (str_contains($name, '[email]')) {
                $formData[$name] = 'test_' . uniqid() . '@example.com';
            } elseif (str_contains($name, '[plainPassword]')) {
                $formData[$name] = '123';
            }
        }

        $client->submit($form, $formData);

        // DTO validation (Length min=6) → isValid()=false → page re-rendered
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('form');
        $this->assertResponseIsSuccessful();
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
