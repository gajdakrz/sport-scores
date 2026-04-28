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
use App\Enum\TeamType;
use App\Repository\GameResultRepository;
use App\Tests\Trait\ControllerTestTrait;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
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

    // -------------------------------------------------------------------------
    // delete
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Delete request soft-deletes the game result by setting it as inactive')]
    public function deleteSoftDeletesGameResult(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        // setCurrentSport ustawia sport w sesji I zwraca ten sam sport
        $sport        = $this->setCurrentSport($client);
        $competition  = $this->createTestCompetition($sport);
        $event        = $this->createTestEvent($competition);
        $season       = $this->createTestSeason();
        $game         = $this->createTestGame($event, $season);
        $team1        = $this->createTestTeam($sport);
        $team2        = $this->createTestTeam($sport);
        // createActiveByFilterBuilder robi INNER JOIN na opponent –
        // wymaga istnienia DRUGIEGO game result dla tej samej gry
        $gameResult   = $this->createTestGameResult($game, $team1);
        $this->createTestGameResult($game, $team2); // opponent – wymagany przez join
        $gameResultId = $gameResult->getId();

        $crawler      = $client->request('GET', '/game-results');
        $deleteButton = $crawler->filter(sprintf('button[data-delete-url*="/game-results/%d"]', $gameResultId));

        $this->assertGreaterThan(0, $deleteButton->count(), 'Delete button not found');

        $client->request('POST', sprintf('/game-results/%d', $gameResultId), [
            '_token' => $deleteButton->attr('data-token'),
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
    // Helpers
    // -------------------------------------------------------------------------

    private function getEntityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        return $em;
    }

    private function createTestCompetition(Sport $sport): Competition
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $competition = new Competition();
        $competition->setName('Test Competition ' . uniqid());
        $competition->setSport($sport);
        $competition->setGender(Gender::MALE);
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

    private function createTestTeam(Sport $sport): Team
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $team = new Team();
        $team->setName('Test Team ' . uniqid());
        $team->setSport($sport);
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

    private function getValidCsrfToken(KernelBrowser $client, string $url): string
    {
        $crawler = $client->request('GET', $url);

        foreach ($crawler->filter('input[type="hidden"]') as $input) {
            /** @var \DOMElement $input */
            $name = $input->getAttribute('name');
            if (str_contains($name, '_token')) {
                return $input->getAttribute('value');
            }
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    private function assertJsonSuccessResponse(KernelBrowser $client): array
    {
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $this->assertJson($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);

        return $data;
    }
}
