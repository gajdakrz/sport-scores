<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web;

use App\Tests\Trait\ControllerTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

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

        // RegistrationFormType nie ma pola role → domyślnie Role::USER.
        // Rejestracja usera wymaga istniejącego admina.
        $this->ensureAdminExists($client);

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
        $this->ensureAdminExists($client);

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
    // POST /register – formularz nieprawidłowy (!isValid())
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the form with invalid CSRF token re-renders the form without redirect')]
    public function submittingFormWithInvalidCsrfTokenRendersFormAgain(): void
    {
        $client = self::createClient();

        // RegistrationFormType ma tylko EmailType i PasswordType – żadnych enumów.
        // Jedyna droga do !isValid() (poza walidacją DTO) to nieprawidłowy CSRF.
        $client->request('POST', '/register', [
            'registration_form' => [
                'email'         => 'test@example.com',
                'plainPassword' => 'Secret123!',
                '_token'        => 'invalid_csrf_token',
            ],
        ]);

        // Formularz nieprawidłowy → kontroler renderuje stronę ponownie (nie redirect)
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

        // Walidacja DTO (NotBlank na email) → isValid()=false → re-render strony
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('form');
        $this->assertResponseIsSuccessful(); // brak redirect = 200
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

        // Walidacja DTO (Length min=6) → isValid()=false → re-render strony
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('form');
        $this->assertResponseIsSuccessful();
    }

    // -------------------------------------------------------------------------
    // Dostępność dla zalogowanych
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
     * Rejestracja wymaga istnienia admina (RegistrationUserService::register()).
     * Tworzymy go przez API endpoint który obsługuje role.
     */
    private function ensureAdminExists(KernelBrowser $client): void
    {
        $client->loginUser($this->getTestUser(), 'api');

        $client->request(
            method: 'POST',
            uri: '/api/v1/user',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email'         => 'admin_setup_' . uniqid() . '@example.com',
                'plainPassword' => $_ENV['TEST_ADMIN_PASSWORD'],
                'role'          => 'ROLE_ADMIN',
            ], JSON_THROW_ON_ERROR),
        );
        // 201 = admin utworzony, 409 = już istnieje – oba są OK
    }
}
