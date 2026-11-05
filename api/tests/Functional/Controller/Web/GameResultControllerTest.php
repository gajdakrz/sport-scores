<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web;

use App\Entity\Competition;
use App\Entity\Event;
use App\Entity\Game;
use App\Entity\GameResult;
use App\Entity\Season;
use App\Entity\Sport;
use App\Entity\Team;
use App\Enum\Gender;
use App\Enum\GenderCategory;
use App\Enum\TeamType;
use App\Repository\GameResultRepository;
use App\Tests\Trait\ControllerTestTrait;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class GameResultControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Index page is accessible for authenticated user')]
    public function indexIsAccessibleForAuthenticatedUser(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/game-results');

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    #[TestDox('Index page redirects unauthenticated users to the login page')]
    public function indexRedirectsUnauthenticatedUsers(): void
    {
        $client = self::createClient();

        $client->request('GET', '/game-results');

        $this->assertResponseRedirects();
    }

    #[Test]
    #[TestDox('Index page accepts optional filter query parameters without error')]
    public function indexAcceptsFilterQueryParameters(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/game-results', ['page' => 1, 'limit' => 10]);

        $this->assertResponseIsSuccessful();
    }

    // -------------------------------------------------------------------------
    // new – GET
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('New page displays the game result form when sport is selected')]
    public function newPageDisplaysForm(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $this->setCurrentSport($client);

        $client->request('GET', '/game-results/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    #[Test]
    #[TestDox('New endpoint returns 400 when no sport is selected')]
    public function newReturnsBadRequestWhenNoSportSelected(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $client->request('GET', '/game-results/new', [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // -------------------------------------------------------------------------
    // new – POST (tworzenie)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the new form creates a game result and returns 201 JSON success')]
    public function submittingNewFormCreatesGameResult(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $sport = $this->setCurrentSport($client);

        $competition = $this->createTestCompetition($sport);
        $event       = $this->createTestEvent($competition);
        $season      = $this->createTestSeason();
        $game        = $this->createTestGame($event, $season);
        $team        = $this->createTestTeam($sport);

        $client->request('POST', '/game-results/new', [
            'game_result' => [
                'game'         => (string) $game->getId(),
                'team'         => (string) $team->getId(),
                'matchScore'   => '2',
                'rankingScore' => '',
                '_token'       => $this->getValidCsrfToken($client, '/game-results/new'),
            ],
        ]);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var GameResultRepository $repository */
        $repository = static::getContainer()->get(GameResultRepository::class);
        $result     = $repository->findOneBy(['game' => $game->getId(), 'team' => $team->getId()]);

        $this->assertNotNull($result);
        $this->assertSame(2, $result->getMatchScore());
        $this->assertTrue($result->isActive());
    }

    // -------------------------------------------------------------------------
    // new – POST (walidacja: nieistniejący team → !isValid())
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the new form with a non-existent team ID returns 400')]
    public function submittingNewFormWithInvalidTeamReturns400(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $sport = $this->setCurrentSport($client);

        $competition = $this->createTestCompetition($sport);
        $event       = $this->createTestEvent($competition);
        $season      = $this->createTestSeason();
        $game        = $this->createTestGame($event, $season);

        // Nieistniejące ID → EntityType nie znajdzie encji w choices
        // → TransformationFailedException → isSynchronized()=false → isValid()=false
        $client->request('POST', '/game-results/new', [
            'game_result' => [
                'game'         => (string) $game->getId(),
                'team'         => '999999',
                'matchScore'   => '1',
                'rankingScore' => '',
                '_token'       => $this->getValidCsrfToken($client, '/game-results/new'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertArrayHasKey('errors', $data);
        $errors = $data['errors'];
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
    }

    #[Test]
    #[TestDox('Submitting the new form without a team returns 400 with error on "team"')]
    public function submittingNewFormWithoutTeamReturnsBadRequest(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $sport = $this->setCurrentSport($client);

        $competition = $this->createTestCompetition($sport);
        $event       = $this->createTestEvent($competition);
        $season      = $this->createTestSeason();
        $game        = $this->createTestGame($event, $season);

        $client->request('POST', '/game-results/new', [
            'game_result' => [
                'game'         => (string) $game->getId(),
                'team'         => '',
                'matchScore'   => '1',
                'rankingScore' => '',
                '_token'       => $this->getValidCsrfToken($client, '/game-results/new'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertIsArray($data['errors']);
        $this->assertContains('team', array_column($data['errors'], 'field'));
    }

    // -------------------------------------------------------------------------
    // edit – GET
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Edit page displays the game result form prefilled with existing data')]
    public function editPageDisplaysForm(): void
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

        $client->request('GET', sprintf('/game-results/%d/edit', $gameResult->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    // -------------------------------------------------------------------------
    // edit – POST (aktualizacja)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting the edit form updates the game result match score in the database')]
    public function submittingEditFormUpdatesGameResult(): void
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

        // DomCrawler zamiast surowego POST – form->handleRequest() poprawnie
        // mapuje encję (game zostaje niezmienione, bo nie ma go w formularzu)
        $crawler = $client->request('GET', sprintf('/game-results/%d/edit', $gameResult->getId()));
        $form    = $crawler->selectButton('Save')->form();

        $formData = [];
        foreach ($form->all() as $field) {
            $name = $field->getName();
            if (str_contains($name, '[matchScore]')) {
                $formData[$name] = '5';
            }
        }

        $client->submit($form, $formData);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var GameResultRepository $repository */
        $repository = static::getContainer()->get(GameResultRepository::class);
        $updated    = $repository->find($gameResult->getId());

        $this->assertNotNull($updated);
        $this->assertSame(5, $updated->getMatchScore());
    }

    #[Test]
    #[TestDox('Submitting the edit form with a non-existent team ID returns 400')]
    public function submittingEditFormWithInvalidTeamReturns400(): void
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

        $client->request('POST', sprintf('/game-results/%d/edit', $gameResult->getId()), [
            'game_result' => [
                'team'         => '999999',
                'matchScore'   => '1',
                'rankingScore' => '',
                '_token'       => $this->getValidCsrfToken(
                    $client,
                    sprintf('/game-results/%d/edit', $gameResult->getId())
                ),
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertArrayHasKey('errors', $data);
    }

    /**
     * @return array<string, array{int, bool}>
     */
    public static function moveToAnotherGameProvider(): array
    {
        return [
            'original game would keep one result'  => [2, false],
            'original game would keep two results' => [3, true],
        ];
    }

    #[Test]
    #[DataProvider('moveToAnotherGameProvider')]
    #[TestDox('Moving a result out of a game with $resultCount results is allowed: $allowed')]
    public function movingResultToAnotherGameRespectsMinimumResults(int $resultCount, bool $allowed): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport        = $this->createTestSport();
        $event        = $this->createTestEvent($this->createTestCompetition($sport));
        $season       = $this->createTestSeason();
        $originalGame = $this->createTestGame($event, $season);
        $otherGame    = $this->createTestGame($event, $season);
        $gameResult   = $this->createTestGameResults($originalGame, $sport, $resultCount)[0];
        $editUrl      = sprintf('/game-results/%d/edit', $gameResult->getId());

        $client->request('POST', $editUrl, [
            'game_result' => [
                'game'         => (string) $otherGame->getId(),
                'team'         => (string) $gameResult->getTeam()?->getId(),
                'matchScore'   => '1',
                'rankingScore' => '',
                '_token'       => $this->getValidCsrfToken($client, $editUrl),
            ],
        ]);

        $data = $this->assertJsonSuccessResponse($client);
        $expectedGame = $allowed ? $otherGame : $originalGame;

        if ($allowed) {
            $this->assertTrue($data['success']);
        } else {
            $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
            $this->assertIsArray($data['errors']);
            $this->assertContains('game', array_column($data['errors'], 'field'));
        }

        $this->getEntityManager()->clear();
        /** @var GameResultRepository $repository */
        $repository = static::getContainer()->get(GameResultRepository::class);
        $this->assertSame($expectedGame->getId(), $repository->find($gameResult->getId())?->getGame()?->getId());
    }

    // -------------------------------------------------------------------------
    // delete
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Delete request soft-deletes the game result when the game keeps at least two results')]
    public function deleteSoftDeletesGameResult(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        // setCurrentSport ustawia sport w sesji I zwraca ten sam sport
        $sport       = $this->setCurrentSport($client);
        $competition = $this->createTestCompetition($sport);
        $game        = $this->createTestGame($this->createTestEvent($competition), $this->createTestSeason());
        // the game keeps two results after the delete (index also needs an opponent for the INNER JOIN)
        $gameResultId = (int) $this->createTestGameResults($game, $sport, 3)[0]->getId();

        $client->request('POST', sprintf('/game-results/%d', $gameResultId), [
            '_token' => $this->getDeleteToken($client, $gameResultId),
        ]);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertTrue($data['success']);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var GameResultRepository $repository */
        $repository = static::getContainer()->get(GameResultRepository::class);
        $deleted    = $repository->find($gameResultId);

        $this->assertNotNull($deleted);
        $this->assertFalse($deleted->isActive());
    }

    #[Test]
    #[TestDox('Delete request returns 400 and keeps the result when the game would be left with fewer than two')]
    public function deleteBelowMinimumResultsReturnsBadRequest(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $sport       = $this->setCurrentSport($client);
        $competition = $this->createTestCompetition($sport);
        $game        = $this->createTestGame($this->createTestEvent($competition), $this->createTestSeason());
        $gameResultId = (int) $this->createTestGameResults($game, $sport, 2)[0]->getId();

        $client->request('POST', sprintf('/game-results/%d', $gameResultId), [
            '_token' => $this->getDeleteToken($client, $gameResultId),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = $this->assertJsonSuccessResponse($client);
        $this->assertIsArray($data['errors']);
        $this->assertNotEmpty($data['errors']);

        $this->getEntityManager()->clear();
        /** @var GameResultRepository $repository */
        $repository = static::getContainer()->get(GameResultRepository::class);
        $this->assertTrue($repository->find($gameResultId)?->isActive());
    }

    #[Test]
    #[TestDox('Delete request with invalid CSRF token returns 403 and leaves game result active')]
    public function deleteWithInvalidCsrfTokenReturnsForbidden(): void
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

        $client->request('POST', sprintf('/game-results/%d', $gameResult->getId()), [
            '_token' => 'invalid_token',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $em = $this->getEntityManager();
        $em->clear();

        /** @var GameResultRepository $repository */
        $repository  = static::getContainer()->get(GameResultRepository::class);
        $stillActive = $repository->find($gameResult->getId());

        $this->assertNotNull($stillActive);
        $this->assertTrue($stillActive->isActive());
    }

    // -------------------------------------------------------------------------
    // new – POST (walidacja płci: drużyna vs rozgrywki)
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Submitting a male team for a female competition returns 400 with error on "team"')]
    public function submittingTeamWithMismatchedGenderReturns400(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $sport = $this->setCurrentSport($client);

        $competition = $this->createTestCompetition($sport, GenderCategory::FEMALE);
        $game        = $this->createTestGame($this->createTestEvent($competition), $this->createTestSeason());
        $team        = $this->createTestTeam($sport, GenderCategory::MALE);

        $client->request('POST', '/game-results/new', [
            'game_result' => [
                'game'       => (string) $game->getId(),
                'team'       => (string) $team->getId(),
                'matchScore' => '1',
                '_token'     => $this->getValidCsrfToken($client, '/game-results/new'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertIsArray($data['errors']);
        $this->assertContains('team', array_column($data['errors'], 'field'));
    }

    #[Test]
    #[TestDox('Submitting a mixed team for a female competition returns 400 with error on "team"')]
    public function submittingMixedTeamForFemaleCompetitionReturns400(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $sport = $this->setCurrentSport($client);

        $competition = $this->createTestCompetition($sport, GenderCategory::FEMALE);
        $game        = $this->createTestGame($this->createTestEvent($competition), $this->createTestSeason());
        $team        = $this->createTestTeam($sport, GenderCategory::MIXED);

        $client->request('POST', '/game-results/new', [
            'game_result' => [
                'game'       => (string) $game->getId(),
                'team'       => (string) $team->getId(),
                'matchScore' => '1',
                '_token'     => $this->getValidCsrfToken($client, '/game-results/new'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->assertJsonSuccessResponse($client);
        $this->assertIsArray($data['errors']);
        $this->assertContains('team', array_column($data['errors'], 'field'));
    }

    #[Test]
    #[TestDox('Submitting a mixed team for a mixed competition creates the game result')]
    public function submittingMixedTeamForMixedCompetitionIsAccepted(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);
        $sport = $this->setCurrentSport($client);

        $competition = $this->createTestCompetition($sport, GenderCategory::MIXED);
        $game        = $this->createTestGame($this->createTestEvent($competition), $this->createTestSeason());
        $team        = $this->createTestTeam($sport, GenderCategory::MIXED);

        $client->request('POST', '/game-results/new', [
            'game_result' => [
                'game'       => (string) $game->getId(),
                'team'       => (string) $team->getId(),
                'matchScore' => '1',
                '_token'     => $this->getValidCsrfToken($client, '/game-results/new'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @return list<GameResult>
     */
    private function createTestGameResults(Game $game, Sport $sport, int $count): array
    {
        $results = [];
        for ($i = 0; $i < $count; $i++) {
            $results[] = $this->createTestGameResult($game, $this->createTestTeam($sport));
        }

        return $results;
    }

    private function getDeleteToken(KernelBrowser $client, int $gameResultId): string
    {
        $crawler      = $client->request('GET', '/game-results');
        $deleteButton = $crawler->filter(sprintf('button[data-delete-url*="/game-results/%d"]', $gameResultId));

        $this->assertGreaterThan(0, $deleteButton->count(), 'Delete button not found');

        return (string) $deleteButton->attr('data-token');
    }

    private function createTestCompetition(Sport $sport, GenderCategory $gender = GenderCategory::MALE): Competition
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $competition = new Competition();
        $competition->setName('Test Competition ' . uniqid());
        $competition->setSport($sport);
        $competition->setGender($gender);
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

    private function createTestTeam(Sport $sport, GenderCategory $gender = GenderCategory::MALE): Team
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $team = new Team();
        $team->setName('Test Team ' . uniqid());
        $team->setSport($sport);
        $team->setGender($gender);
        $team->setTeamType(TeamType::CLUB);
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

        $gameResult = new GameResult();
        $game->addGameResult($gameResult);
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
