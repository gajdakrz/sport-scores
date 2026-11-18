<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web\User;

use App\Entity\Competition;
use App\Entity\Sport;
use App\Entity\Team;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Repository\UserPreferenceRepository;
use App\Tests\Trait\ControllerTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\HttpFoundation\Response;

final class PreferenceControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    private const string EDIT_URL = '/preferences/edit';
    private const string FIELD_SPORT = 'favouriteSport';
    private const string FIELD_COMPETITION = 'favouriteCompetition';
    private const string FIELD_TEAM = 'favouriteTeam';
    private const string FIELD_CURRENT_SEASON = 'currentSeasonByDefault';

    // -------------------------------------------------------------------------
    // edit
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Preferences form is prefilled with the stored favourites')]
    public function preferencesFormIsPrefilledWithStoredFavourites(): void
    {
        $client = self::createClient();
        $user   = $this->loginAsTestUser($client);
        $sport  = $this->createTestSport();
        $team   = $this->createTeamInSport($sport);
        $this->saveUserPreference($user, $sport, $team, false);

        $crawler = $client->request('GET', self::EDIT_URL);

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            (string) $team->getId(),
            $crawler->filter('#preference_favouriteTeam option[selected]')->attr('value')
        );
        $this->assertCheckboxNotChecked('preference[currentSeasonByDefault]');
    }

    #[Test]
    #[TestDox('Every preference field is rendered in the modal body, above the buttons')]
    public function everyPreferenceFieldIsInModalBody(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', self::EDIT_URL);

        foreach ([self::FIELD_SPORT, self::FIELD_COMPETITION, self::FIELD_TEAM, self::FIELD_CURRENT_SEASON] as $field) {
            $this->assertSelectorExists(sprintf('.modal-body #preference_%s', $field));
        }
    }

    #[Test]
    #[TestDox('Favourite sport, competition, team and the "Current season" default are saved for the logged-in user')]
    public function favouritesAreSaved(): void
    {
        $client      = self::createClient();
        $user        = $this->loginAsTestUser($client);
        $sport       = $this->createTestSport();
        $team        = $this->createTeamInSport($sport);
        $competition = $this->createCompetitionInSport($sport);

        $this->submitPreferenceForm($client, $sport, $team, false, $competition);

        $this->assertResponseIsSuccessful();
        $this->assertTrue($this->assertJsonSuccessResponse($client)['success']);

        $stored = $this->findStoredPreference($user);
        $this->assertSame($sport->getId(), $stored->getFavouriteSport()?->getId());
        $this->assertSame($competition->getId(), $stored->getFavouriteCompetition()?->getId());
        $this->assertSame($team->getId(), $stored->getFavouriteTeam()?->getId());
        $this->assertFalse($stored->isCurrentSeasonByDefault());
    }

    #[Test]
    #[TestDox('Saving an empty form clears the stored favourites')]
    public function emptyFormClearsFavourites(): void
    {
        $client = self::createClient();
        $user   = $this->loginAsTestUser($client);
        $sport  = $this->createTestSport();
        $this->saveUserPreference(
            $user,
            $sport,
            $this->createTeamInSport($sport),
            false,
            $this->createCompetitionInSport($sport),
        );

        $this->submitPreferenceForm($client, null, null, true);

        $this->assertResponseIsSuccessful();
        $stored = $this->findStoredPreference($user);
        $this->assertNull($stored->getFavouriteSport());
        $this->assertNull($stored->getFavouriteCompetition());
        $this->assertNull($stored->getFavouriteTeam());
        $this->assertTrue($stored->isCurrentSeasonByDefault());
    }

    #[Test]
    #[TestDox('Favourite team of another sport than the favourite one is rejected')]
    public function favouriteTeamOfAnotherSportIsRejected(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $team = $this->createTeamInSport($this->createTestSport());

        $this->submitPreferenceForm($client, $this->createTestSport(), $team, true);

        $this->assertFavouriteRejected($client, self::FIELD_TEAM);
    }

    #[Test]
    #[TestDox('Favourite team without a favourite sport is rejected')]
    public function favouriteTeamWithoutFavouriteSportIsRejected(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $team = $this->createTeamInSport($this->createTestSport());

        $this->submitPreferenceForm($client, null, $team, true);

        $this->assertFavouriteRejected($client, self::FIELD_TEAM);
    }

    #[Test]
    #[TestDox('Favourite competition of another sport than the favourite one is rejected')]
    public function favouriteCompetitionOfAnotherSportIsRejected(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $competition = $this->createCompetitionInSport($this->createTestSport());

        $this->submitPreferenceForm($client, $this->createTestSport(), null, true, $competition);

        $this->assertFavouriteRejected($client, self::FIELD_COMPETITION);
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
    // menu links preset from the preferences
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Menu links to team lists preset the favourite team while its sport is the current one')]
    public function menuLinksPresetFavouriteTeamOfCurrentSport(): void
    {
        $client = self::createClient();
        $user   = $this->loginAsTestUser($client);
        $sport  = $this->createTestSport();
        $team   = $this->createTeamInSport($sport);
        $this->saveUserPreference($user, $sport, $team);

        $client->request('POST', sprintf('/sports/set/%d', $sport->getId()));
        $crawler = $client->request('GET', '/sports');

        $teamQuery = sprintf('?teamId=%d', $team->getId());
        $this->assertCount(1, $crawler->filter(sprintf('a[href="/game-results%s"]', $teamQuery)));
        $this->assertCount(1, $crawler->filter(sprintf('a[href="/team-members%s"]', $teamQuery)));
        // Teams filters by name, encoded the way the router does it
        $nameQuery = http_build_query(['name' => $team->getName()], '', '&', PHP_QUERY_RFC3986);
        $this->assertCount(1, $crawler->filter(sprintf('a[href="/teams?%s"]', $nameQuery)));
    }

    #[Test]
    #[TestDox('Menu links don\'t preset the favourite team when another sport is the current one')]
    public function menuLinksSkipFavouriteTeamOfAnotherSport(): void
    {
        $client = self::createClient();
        $user   = $this->loginAsTestUser($client);
        $sport  = $this->createTestSport();
        $this->saveUserPreference($user, $sport, $this->createTeamInSport($sport));

        $client->request('POST', sprintf('/sports/set/%d', $this->createTestSport()->getId()));
        $crawler = $client->request('GET', '/sports');

        $this->assertCount(1, $crawler->filter('a[href="/game-results"]'));
        $this->assertCount(1, $crawler->filter('a[href="/team-members"]'));
        $this->assertCount(1, $crawler->filter('a[href="/teams"]'));
    }

    #[Test]
    #[TestDox('Menu links to Events and Games preset the favourite competition while its sport is the current one')]
    public function menuLinksPresetFavouriteCompetitionOfCurrentSport(): void
    {
        $client      = self::createClient();
        $user        = $this->loginAsTestUser($client);
        $sport       = $this->createTestSport();
        $competition = $this->createCompetitionInSport($sport);
        $this->saveUserPreference($user, $sport, null, false, $competition);

        $client->request('POST', sprintf('/sports/set/%d', $sport->getId()));
        $crawler = $client->request('GET', '/sports');

        $competitionQuery = sprintf('?competitionId=%d', $competition->getId());
        $this->assertCount(1, $crawler->filter(sprintf('a[href="/events%s"]', $competitionQuery)));
        $this->assertCount(1, $crawler->filter(sprintf('a[href="/games%s"]', $competitionQuery)));
    }

    #[Test]
    #[TestDox('Team season stats links on Teams preset the favourite competition of the current sport')]
    public function teamSeasonStatsLinksPresetFavouriteCompetition(): void
    {
        $client      = self::createClient();
        $user        = $this->loginAsTestUser($client);
        $sport       = $this->createTestSport();
        $team        = $this->createTeamInSport($sport);
        $competition = $this->createCompetitionInSport($sport);
        $this->saveUserPreference($user, $sport, null, true, $competition);

        $client->request('POST', sprintf('/sports/set/%d', $sport->getId()));
        $crawler = $client->request('GET', '/teams');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter(sprintf(
            'a[href="/teams/%d/result-season-index?competitionId=%d"]',
            $team->getId(),
            $competition->getId()
        )));
    }

    #[Test]
    #[TestDox('Games menu link opens without the "Current season" filter when the user switched it off')]
    public function gamesMenuLinkSkipsCurrentSeasonWhenSwitchedOff(): void
    {
        $client = self::createClient();
        $user   = $this->loginAsTestUser($client);
        $this->saveUserPreference($user, null, null, false);

        $crawler = $client->request('GET', '/sports');

        $this->assertCount(1, $crawler->filter('a[href="/games"]'));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Submits the real modal form (CSRF token included); null leaves the select at "None".
     */
    private function submitPreferenceForm(
        KernelBrowser $client,
        ?Sport $sport,
        ?Team $team,
        bool $currentSeasonByDefault,
        ?Competition $competition = null,
    ): void {
        $form = $client->request('GET', self::EDIT_URL)->selectButton('Save')->form();

        $form['preference[' . self::FIELD_SPORT . ']'] = (string) $sport?->getId();
        // the selects list options of every sport, so any id is a valid choice for the form itself
        $form['preference[' . self::FIELD_COMPETITION . ']'] = (string) $competition?->getId();
        $form['preference[' . self::FIELD_TEAM . ']'] = (string) $team?->getId();

        $checkbox = $form['preference[' . self::FIELD_CURRENT_SEASON . ']'];
        $this->assertInstanceOf(ChoiceFormField::class, $checkbox);
        $currentSeasonByDefault ? $checkbox->tick() : $checkbox->untick();

        $client->submit($form);
    }

    private function assertFavouriteRejected(KernelBrowser $client, string $field): void
    {
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $errors = $this->assertJsonSuccessResponse($client)['errors'];
        $this->assertIsArray($errors);
        $this->assertContains($field, array_column($errors, 'field'));
    }

    private function findStoredPreference(User $user): UserPreference
    {
        $this->getEntityManager()->clear();

        /** @var UserPreferenceRepository $repository */
        $repository = static::getContainer()->get(UserPreferenceRepository::class);
        $stored = $repository->findOneBy(['user' => $user->getId()]);
        $this->assertNotNull($stored);

        return $stored;
    }
}
