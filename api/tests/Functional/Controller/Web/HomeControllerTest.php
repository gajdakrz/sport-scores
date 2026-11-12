<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web;

use App\Tests\Trait\ControllerTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomeControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    public function testIndex(): void
    {
        $client = static::createClient();
        $this->loginAsTestUser($client);
        $client->request('GET', '/');
        self::assertResponseIsSuccessful();
    }

    #[Test]
    #[TestDox('Navbar shows the logged-in user name, initials, profile edit and a CSRF-protected logout link')]
    public function navbarShowsLoggedInUserWithLogoutLink(): void
    {
        $client = static::createClient();
        $user   = $this->loginAsTestUser($client);

        $client->request('GET', '/');

        self::assertSelectorTextContains('#user-menu', $user->getFullName());
        self::assertSelectorTextContains('#user-menu .user-avatar', $user->getInitials());
        self::assertSelectorExists('#user-menu #editProfileBtn');
        self::assertSelectorExists('#user-menu a[href^="/logout?_csrf_token="]');
    }

    #[Test]
    #[TestDox('Navbar has no user menu for an anonymous visitor')]
    public function navbarHasNoUserMenuForAnonymousVisitor(): void
    {
        $client = static::createClient();

        $client->request('GET', '/login');

        self::assertSelectorNotExists('#user-menu');
    }
}
