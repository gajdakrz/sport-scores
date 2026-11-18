<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web;

use App\Tests\Trait\ControllerTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomeControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    #[Test]
    #[TestDox('Home page shows the sport selection to a user without a current or favourite sport')]
    public function homeShowsSportSelectionWithoutKnownSport(): void
    {
        $client = static::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('#sport-select');
    }

    #[Test]
    #[TestDox('Home page leads to Games when a sport is already selected in the session (e.g. in another tab)')]
    public function homeLeadsToGamesWithSportInSession(): void
    {
        $client = static::createClient();
        $this->loginAsTestUser($client);
        $this->setCurrentSport($client);

        $client->request('GET', '/');

        $this->assertRedirectsToGames($client);
    }

    #[Test]
    #[TestDox('Home page leads to Games with the favourite sport set as the current one')]
    public function homeLeadsToGamesWithFavouriteSport(): void
    {
        $client = static::createClient();
        $user   = $this->loginAsTestUser($client);
        $sport  = $this->createTestSport();
        $this->saveUserPreference($user, $sport);

        $client->request('GET', '/');

        $this->assertRedirectsToGames($client);
        $client->followRedirect();
        self::assertSelectorTextContains('#sport-switcher option[selected]', (string) $sport->getName());
    }

    private function assertRedirectsToGames(KernelBrowser $client): void
    {
        self::assertResponseRedirects();
        self::assertStringStartsWith('/games', (string) $client->getResponse()->headers->get('Location'));
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
