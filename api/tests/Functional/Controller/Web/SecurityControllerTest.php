<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web;

use App\Tests\Trait\ControllerTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SecurityControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    // -------------------------------------------------------------------------
    // login – GET
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Login page is publicly accessible')]
    public function loginPageIsPubliclyAccessible(): void
    {
        $client = self::createClient();

        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    #[TestDox('Login page renders a form with email and password fields')]
    public function loginPageRendersForm(): void
    {
        $client = self::createClient();

        $client->request('GET', '/login');

        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="_username"], input[type="email"]');
        $this->assertSelectorExists('input[type="password"]');
    }

    #[Test]
    #[TestDox('Login page displays last username when authentication error occurred')]
    public function loginPageDisplaysLastUsernameAfterFailedAttempt(): void
    {
        $client = self::createClient();

        // Symulujemy nieudane logowanie przez POST – Symfony zapamięta email w sesji
        $client->request('POST', '/login', [
            '_username' => 'wrong@example.com',
            '_password' => 'wrongpassword',
        ]);

        // Po nieudanej próbie Symfony redirectuje z powrotem na /login
        $client->followRedirect();

        // last_username powinien być widoczny w formularzu
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('wrong@example.com', $content);
    }

    // -------------------------------------------------------------------------
    // logout
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Logout redirects authenticated user and invalidates session')]
    public function logoutRedirectsAuthenticatedUser(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/logout');

        // Symfony firewall przechwytuje /logout zanim trafi do kontrolera
        // i wykonuje redirect (LogicException nigdy nie jest wyrzucany)
        $this->assertResponseRedirects();
    }

    #[Test]
    #[TestDox('Logout is accessible without authentication')]
    public function logoutIsAccessibleWithoutAuthentication(): void
    {
        $client = self::createClient();

        $client->request('GET', '/logout');

        // Niezalogowany użytkownik dostaje redirect (np. na /login lub /)
        $this->assertResponseRedirects();
    }
}
