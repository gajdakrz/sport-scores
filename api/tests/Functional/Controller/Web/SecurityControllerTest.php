<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web;

use App\Entity\User;
use App\Enum\Role;
use App\Tests\Trait\ControllerTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

final class SecurityControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    private const string DEACTIVATED_USER_PASSWORD = 'password';

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

        // Simulate a failed login via POST – Symfony keeps the email in the session
        $client->request('POST', '/login', [
            '_username' => 'wrong@example.com',
            '_password' => 'wrongpassword',
        ]);

        // After a failed attempt Symfony redirects back to /login
        $client->followRedirect();

        // last_username should be shown in the form
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('wrong@example.com', $content);
    }

    // -------------------------------------------------------------------------
    // login – deactivated account
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Deactivated account with a wrong password gets the same error as unknown credentials')]
    public function deactivatedAccountWithWrongPasswordDoesNotRevealAccountStatus(): void
    {
        $client = self::createClient();
        $email  = $this->createDeactivatedUser();

        $this->submitLoginForm($client, $email, 'wrong-password');

        $this->assertResponseRedirects('/login');
        $this->assertInstanceOf(BadCredentialsException::class, $this->lastAuthenticationError($client));
    }

    #[Test]
    #[TestDox('Deactivated account with the correct password is rejected as deactivated')]
    public function deactivatedAccountWithCorrectPasswordIsRejected(): void
    {
        $client = self::createClient();
        $email  = $this->createDeactivatedUser();

        $this->submitLoginForm($client, $email, self::DEACTIVATED_USER_PASSWORD);

        $this->assertResponseRedirects('/login');
        $this->assertInstanceOf(
            CustomUserMessageAccountStatusException::class,
            $this->lastAuthenticationError($client)
        );
    }

    // -------------------------------------------------------------------------
    // logout
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Logout link from the navbar ends the session')]
    public function logoutFromNavbarEndsSession(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $crawler = $client->request('GET', '/');
        $client->click($crawler->filter('#user-menu [data-test="logout-link"]')->link());

        $this->assertResponseRedirects();

        $client->request('GET', '/');
        $this->assertResponseRedirects('/login');
    }

    #[Test]
    #[TestDox('Logout without a CSRF token is rejected and the user stays logged in')]
    public function logoutWithoutCsrfTokenIsRejected(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/logout');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @return string the email of the created user
     */
    private function createDeactivatedUser(): string
    {
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail('deactivated-' . uniqid() . '@example.com');
        $user->setRoles([Role::USER->value]);
        $user->setPassword($passwordHasher->hashPassword($user, self::DEACTIVATED_USER_PASSWORD));
        $user->setIsActive(false);

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return (string) $user->getEmail();
    }

    private function submitLoginForm(KernelBrowser $client, string $email, string $password): void
    {
        // the real form, so the CSRF token (enable_csrf: true) is sent as well
        $form = $client->request('GET', '/login')->selectButton('Sign in')->form();

        $client->submit($form, ['_username' => $email, '_password' => $password]);
    }

    private function lastAuthenticationError(KernelBrowser $client): mixed
    {
        return $client->getRequest()->getSession()->get(SecurityRequestAttributes::AUTHENTICATION_ERROR);
    }
}
