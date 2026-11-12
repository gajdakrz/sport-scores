<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web;

use App\Entity\Competition;
use App\Entity\Country;
use App\Entity\Event;
use App\Entity\Game;
use App\Entity\GameResult;
use App\Entity\Team;
use App\Enum\TeamType;
use App\Entity\Season;
use App\Entity\Sport;
use App\Enum\GenderCategory;
use App\Repository\GameRepository;
use App\Repository\GameResultRepository;
use App\Tests\Trait\ControllerTestTrait;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class GameControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    private const string CLEAR_LINK = 'a[data-test="filter-clear"]';

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Index page is accessible for authenticated user')]
    public function indexIsAccessibleForAuthenticatedUser(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/games');

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    #[TestDox('Index page redirects unauthenticated users to the login page')]
    public function indexRedirectsUnauthenticatedUsers(): void
    {
        $client = self::createClient();

        $client->request('GET', '/games');

        $this->assertResponseRedirects();
    }

    #[Test]
    #[TestDox('Index page accepts optional filter query parameters without error')]
    public function indexAcceptsFilterQueryParameters(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/games', ['page' => 1, 'limit' => 10]);

        $this->assertResponseIsSuccessful();
    }

    /**
     * @return array<string, array{array<string, string|int>}>
     */
    public static function activeFilterProvider(): array
    {
        return [
            'gender filter'          => [['gender' => GenderCategory::MALE->value, 'page' => 1]],
        ];
    }

    /**
     * @param array<string, string|int> $query
     */
    #[Test]
    #[DataProvider('activeFilterProvider')]
    #[TestDox('Clear link leads to the unfiltered list with $_dataName applied')]
    public function clearLinkResetsAppliedFilters(array $query): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/games', $query);

        $this->assertSelectorExists(self::CLEAR_LINK . '[href="/games"]');
        $this->assertSelectorNotExists(self::CLEAR_LINK . '.disabled');
    }

    /**
     * @return array<string, array{array<string, string|int>}>
     */
    public static function noActiveFilterProvider(): array
    {
        return [
            'no query'             => [[]],
            'only pagination'      => [['page' => 1, 'limit' => 10]],
            'filter fields as All' => [['seasonId' => '', 'gender' => '', 'competitionId' => '']],
        ];
    }

    /**
     * @param array<string, string|int> $query
     */
    #[Test]
    #[DataProvider('noActiveFilterProvider')]
    #[TestDox('Clear link is disabled with $_dataName')]
    public function clearLinkIsDisabledWithoutActiveFilter(array $query): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/games', $query);

        $this->assertSelectorExists(self::CLEAR_LINK . '.disabled[aria-disabled="true"]');
    }

    // -------------------------------------------------------------------------
    // new – GET
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('New page displays the game form when sport is selected')]
    public function newPageDisplaysForm(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $this->setCurrentSport($client);

        $client->request('GET', '/games/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    #[Test]
    #[TestDox('New endpoint returns 400 when no sport is selected')]
    public function newReturnsBadRequestWhenNoSportSelected(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/games/new', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // -------------------------------------------------------------------------
    // new – POST (create)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the new form creates a game and returns 201 JSON success')]
    public function submittingNewFormCreatesGame(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $sport = $this->setCurrentSport($client);

        $competition = $this->createTestCompetition($sport);
        $event       = $this->createTestEvent($competition);
        $season      = $this->createTestSeason();
        $home        = $this->createTestTeam($sport);
        $away        = $this->createTestTeam($sport);

        $client->request('POST', '/games/new', [
            'game' => [
                'season'      => (string) $season->getId(),
                'competition' => (string) $competition->getId(),
                'event'       => (string) $event->getId(),
                'date'        => date('Y-m-d'),
                'gameResults' => $this->gameResultsData($home, $away),
                '_token'      => $this->getValidCsrfToken($client, '/games/new'),
            ],
        ]);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var GameRepository $repository */
        $repository = static::getContainer()->get(GameRepository::class);
        $game       = $repository->findOneBy(['event' => $event->getId(), 'season' => $season->getId()]);

        $this->assertNotNull($game);
        $this->assertTrue($game->isActive());
        $this->assertCount(2, $game->getGameResults());
    }

    /**
     * @return array<string, array{int}>
     */
    public static function tooFewResultsProvider(): array
    {
        return [
            'no results' => [0],
            'one result' => [1],
        ];
    }

    #[Test]
    #[DataProvider('tooFewResultsProvider')]
    #[TestDox('Submitting the new form with $resultCount result(s) returns 400 with error on "gameResults"')]
    public function submittingNewFormWithTooFewResultsReturnsBadRequest(int $resultCount): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $sport = $this->setCurrentSport($client);

        $competition = $this->createTestCompetition($sport);
        $event       = $this->createTestEvent($competition);
        $season      = $this->createTestSeason();

        $teams = [];
        for ($i = 0; $i < $resultCount; $i++) {
            $teams[] = $this->createTestTeam($sport);
        }

        $client->request('POST', '/games/new', [
            'game' => [
                'season'      => (string) $season->getId(),
                'competition' => (string) $competition->getId(),
                'event'       => (string) $event->getId(),
                'date'        => date('Y-m-d'),
                'gameResults' => $this->gameResultsData(...$teams),
                '_token'      => $this->getValidCsrfToken($client, '/games/new'),
            ],
        ]);

        $this->assertFieldError($client, 'gameResults');

        /** @var GameRepository $repository */
        $repository = static::getContainer()->get(GameRepository::class);
        $this->assertNull($repository->findOneBy(['event' => $event->getId()]));
    }

    #[Test]
    #[TestDox('Submitting the new form with a result without a team returns 400 with error on "team"')]
    public function submittingNewFormWithResultWithoutTeamReturnsBadRequest(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $sport = $this->setCurrentSport($client);

        $competition = $this->createTestCompetition($sport);
        $event       = $this->createTestEvent($competition);
        $season      = $this->createTestSeason();

        $client->request('POST', '/games/new', [
            'game' => [
                'season'      => (string) $season->getId(),
                'competition' => (string) $competition->getId(),
                'event'       => (string) $event->getId(),
                'date'        => date('Y-m-d'),
                'gameResults' => [['team' => '', 'matchScore' => '3']],
                '_token'      => $this->getValidCsrfToken($client, '/games/new'),
            ],
        ]);

        $this->assertFieldError($client, 'team');

        /** @var GameRepository $repository */
        $repository = static::getContainer()->get(GameRepository::class);
        $this->assertNull($repository->findOneBy(['event' => $event->getId()]));
    }

    #[Test]
    #[TestDox('National game with a team playing in another country is rejected on "team" and nothing is saved')]
    public function nationalGameWithTeamPlayingInAnotherCountryIsRejected(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $sport = $this->setCurrentSport($client);

        $country     = $this->createTestCountry();
        $competition = $this->createTestCompetition($sport, $country);
        $event       = $this->createTestEvent($competition);
        $season      = $this->createTestSeason();
        $localTeam   = $this->createTestTeam($sport, $country);
        $foreignTeam = $this->createTestTeam($sport, $this->createTestCountry());

        $client->request('POST', '/games/new', [
            'game' => [
                'season'      => (string) $season->getId(),
                'competition' => (string) $competition->getId(),
                'event'       => (string) $event->getId(),
                'date'        => date('Y-m-d'),
                'gameResults' => $this->gameResultsData($localTeam, $foreignTeam),
                '_token'      => $this->getValidCsrfToken($client, '/games/new'),
            ],
        ]);

        $this->assertFieldError($client, 'team');

        /** @var GameRepository $repository */
        $repository = static::getContainer()->get(GameRepository::class);
        $this->assertNull($repository->findOneBy(['event' => $event->getId()]));
    }

    // -------------------------------------------------------------------------
    // new – POST (validation: no event → !isValid())
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the new form without selecting an event returns 400')]
    public function submittingNewFormWithoutEventReturns400(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $sport = $this->setCurrentSport($client);

        $competition = $this->createTestCompetition($sport);
        $season      = $this->createTestSeason();

        // event is required=true – no choice → null → "This value should not be null"
        // → form->isValid()=false → throwFormErrors() → 400
        $client->request('POST', '/games/new', [
            'game' => [
                'season'      => (string) $season->getId(),
                'competition' => (string) $competition->getId(),
                // Non-existent ID → EntityType won't find the entity in the choices
                // → TransformationFailedException → isSynchronized()=false → isValid()=false
                'event'       => '999999',
                'date'        => date('Y-m-d'),
                'gameResults' => [],
                '_token'      => $this->getValidCsrfToken($client, '/games/new'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertArrayHasKey('errors', $data);
        $errors = $data['errors'];
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
    }

    // -------------------------------------------------------------------------
    // edit – GET
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Edit page displays the game form prefilled with existing data')]
    public function editPageDisplaysForm(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport       = $this->createTestSport();
        $competition = $this->createTestCompetition($sport);
        $event       = $this->createTestEvent($competition);
        $season      = $this->createTestSeason();
        $game        = $this->createTestGame($event, $season);

        $client->request('GET', sprintf('/games/%d/edit', $game->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    // -------------------------------------------------------------------------
    // edit – POST (aktualizacja)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the edit form updates the game in the database')]
    public function submittingEditFormUpdatesGame(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport       = $this->createTestSport();
        $competition = $this->createTestCompetition($sport);
        $event       = $this->createTestEvent($competition);
        $season      = $this->createTestSeason();
        $game        = $this->createTestGame($event, $season);
        $home        = $this->createTestTeam($sport);
        $away        = $this->createTestTeam($sport);

        $newDate = date('Y-m-d', strtotime('+1 day'));

        $client->request('POST', sprintf('/games/%d/edit', $game->getId()), [
            'game' => [
                'season'      => (string) $season->getId(),
                'competition' => (string) $competition->getId(),
                'event'       => (string) $event->getId(),
                'date'        => $newDate,
                'gameResults' => $this->gameResultsData($home, $away),
                '_token'      => $this->getValidCsrfToken($client, sprintf('/games/%d/edit', $game->getId())),
            ],
        ]);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var GameRepository $repository */
        $repository = static::getContainer()->get(GameRepository::class);
        $updated    = $repository->find($game->getId());

        $this->assertNotNull($updated);
        $this->assertSame($newDate, $updated->getDate()->format('Y-m-d'));
    }

    #[Test]
    #[TestDox('Submitting the edit form without selecting an event returns 400')]
    public function submittingEditFormWithoutEventReturns400(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport       = $this->createTestSport();
        $competition = $this->createTestCompetition($sport);
        $event       = $this->createTestEvent($competition);
        $season      = $this->createTestSeason();
        $game        = $this->createTestGame($event, $season);

        $client->request('POST', sprintf('/games/%d/edit', $game->getId()), [
            'game' => [
                'season'      => (string) $season->getId(),
                'competition' => (string) $competition->getId(),
                // Non-existent ID → EntityType won't find the entity in the choices
                // → TransformationFailedException → isSynchronized()=false → isValid()=false
                'event'       => '999999',
                'date'        => date('Y-m-d'),
                'gameResults' => [],
                '_token'      => $this->getValidCsrfToken($client, sprintf('/games/%d/edit', $game->getId())),
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertArrayHasKey('errors', $data);
    }

    #[Test]
    #[TestDox('Leaving a single result in the edit form returns 400 with error on "gameResults" and keeps both')]
    public function submittingEditFormWithOneResultReturnsBadRequest(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport       = $this->createTestSport();
        $competition = $this->createTestCompetition($sport);
        $event       = $this->createTestEvent($competition);
        $season      = $this->createTestSeason();
        $game        = $this->createTestGame($event, $season);
        $home        = $this->createTestTeam($sport);
        $homeResult  = $this->createTestGameResult($game, $home);
        $awayResult  = $this->createTestGameResult($game, $this->createTestTeam($sport));

        $client->request('POST', sprintf('/games/%d/edit', $game->getId()), [
            'game' => [
                'season'      => (string) $season->getId(),
                'competition' => (string) $competition->getId(),
                'event'       => (string) $event->getId(),
                'date'        => date('Y-m-d'),
                'gameResults' => $this->gameResultsData($home),
                '_token'      => $this->getValidCsrfToken($client, sprintf('/games/%d/edit', $game->getId())),
            ],
        ]);

        $this->assertFieldError($client, 'gameResults');

        $this->getEntityManager()->clear();
        /** @var GameResultRepository $repository */
        $repository = static::getContainer()->get(GameResultRepository::class);
        $this->assertTrue($repository->find($homeResult->getId())?->isActive());
        $this->assertTrue($repository->find($awayResult->getId())?->isActive());
    }

    // -------------------------------------------------------------------------
    // delete
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Delete request soft-deletes the game by setting it as inactive')]
    public function deleteSoftDeletesGame(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport       = $this->createTestSport();
        $competition = $this->createTestCompetition($sport);
        $event       = $this->createTestEvent($competition);
        $season      = $this->createTestSeason();
        $game        = $this->createTestGame($event, $season);
        $gameId      = $game->getId();

        $crawler      = $client->request('GET', '/games');
        $deleteButton = $crawler->filter(sprintf('button[data-delete-url*="%d"]', $gameId));

        $this->assertGreaterThan(0, $deleteButton->count(), 'Delete button not found');

        $csrfToken = $deleteButton->attr('data-token');

        $client->request('POST', sprintf('/games/%d', $gameId), [
            '_token' => $csrfToken,
        ]);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var GameRepository $repository */
        $repository = static::getContainer()->get(GameRepository::class);
        $deleted    = $repository->find($gameId);

        $this->assertNotNull($deleted);
        $this->assertFalse($deleted->isActive());
    }

    #[Test]
    #[TestDox('Delete request also deactivates all active GameResults belonging to the game')]
    public function deleteSoftDeletesGameResults(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport       = $this->createTestSport();
        $competition = $this->createTestCompetition($sport);
        $event       = $this->createTestEvent($competition);
        $season      = $this->createTestSeason();
        $game        = $this->createTestGame($event, $season);
        $team        = $this->createTestTeam($sport);
        $gameResult  = $this->createTestGameResult($game, $team);

        $crawler      = $client->request('GET', '/games');
        $deleteButton = $crawler->filter(sprintf('button[data-delete-url*="%d"]', $game->getId()));
        $this->assertGreaterThan(0, $deleteButton->count(), 'Delete button not found');

        $client->request('POST', sprintf('/games/%d', $game->getId()), [
            '_token' => $deleteButton->attr('data-token'),
        ]);

        $this->assertTrue($this->assertJsonSuccessResponse($client)['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var GameResultRepository $repository */
        $repository        = static::getContainer()->get(GameResultRepository::class);
        $deactivatedResult = $repository->find($gameResult->getId());

        $this->assertNotNull($deactivatedResult);
        $this->assertFalse($deactivatedResult->isActive());
    }

    #[Test]
    #[TestDox('Delete request with invalid CSRF token returns 403 and leaves game active')]
    public function deleteWithInvalidCsrfTokenReturnsForbidden(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport       = $this->createTestSport();
        $competition = $this->createTestCompetition($sport);
        $event       = $this->createTestEvent($competition);
        $season      = $this->createTestSeason();
        $game        = $this->createTestGame($event, $season);

        $client->request('POST', sprintf('/games/%d', $game->getId()), [
            '_token' => 'invalid_token',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var GameRepository $repository */
        $repository  = static::getContainer()->get(GameRepository::class);
        $stillActive = $repository->find($game->getId());

        $this->assertNotNull($stillActive);
        $this->assertTrue($stillActive->isActive());
    }

    // -------------------------------------------------------------------------
    // results
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Results page is accessible for an existing game')]
    public function resultsPageIsAccessible(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport       = $this->createTestSport();
        $competition = $this->createTestCompetition($sport);
        $event       = $this->createTestEvent($competition);
        $season      = $this->createTestSeason();
        $game        = $this->createTestGame($event, $season);

        $client->request('GET', sprintf('/games/%d/results', $game->getId()));

        $this->assertResponseIsSuccessful();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @return array<int, array<string, string>>
     */
    private function gameResultsData(Team ...$teams): array
    {
        return array_values(array_map(
            fn(Team $team): array => ['team' => (string) $team->getId(), 'matchScore' => '3'],
            $teams
        ));
    }

    private function assertFieldError(KernelBrowser $client, string $field): void
    {
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertIsArray($data['errors']);
        $this->assertContains($field, array_column($data['errors'], 'field'));
    }

    private function createTestSeason(): Season
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $season = new Season();
        $season->setStartYear(2024);
        $season->setEndYear(2025);
        $season->setCreatedBy($user);
        $season->setModifiedBy($user);
        $season->setIsActive(true);

        $em->persist($season);
        $em->flush();

        return $season;
    }

    private function createTestCompetition(Sport $sport, ?Country $country = null): Competition
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $competition = new Competition();
        $competition->setName('Test Competition ' . uniqid());
        $competition->setSport($sport);
        $competition->setGender(GenderCategory::MALE);
        $competition->setCountry($country);
        $competition->setCreatedBy($user);
        $competition->setModifiedBy($user);
        $competition->setIsActive(true);

        $em->persist($competition);
        $em->flush();

        return $competition;
    }

    private function createTestEvent(Competition $competition): Event
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $event = new Event();
        $event->setName('Test Event ' . uniqid());
        $event->setCompetition($competition);
        $event->setCreatedBy($user);
        $event->setModifiedBy($user);
        $event->setIsActive(true);

        $em->persist($event);
        $em->flush();

        return $event;
    }

    private function createTestGame(Event $event, Season $season): Game
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $game = new Game();
        $game->setEvent($event);
        $game->setSeason($season);
        $game->setDate(new DateTimeImmutable());
        $game->setCreatedBy($user);
        $game->setModifiedBy($user);
        $game->setIsActive(true);

        $em->persist($game);
        $em->flush();

        return $game;
    }

    private function createTestTeam(Sport $sport, ?Country $competitionCountry = null): Team
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $team = new Team();
        $team->setName('Test Team ' . uniqid());
        $team->setSport($sport);
        $team->setCompetitionCountry($competitionCountry);
        $team->setTeamType(TeamType::CLUB);
        $team->setGender(GenderCategory::MALE);
        $team->setCreatedBy($user);
        $team->setModifiedBy($user);
        $team->setIsActive(true);

        $em->persist($team);
        $em->flush();

        return $team;
    }

    private function createTestGameResult(Game $game, Team $team): GameResult
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        // DB constraint chk_team_or_person wymaga team LUB person
        $gameResult = new GameResult();
        $gameResult->setGame($game);
        $gameResult->setTeam($team);
        $gameResult->setMatchScore(1);
        $gameResult->setCreatedBy($user);
        $gameResult->setModifiedBy($user);
        $gameResult->setIsActive(true);

        $em->persist($gameResult);
        $em->flush();

        return $gameResult;
    }
}
