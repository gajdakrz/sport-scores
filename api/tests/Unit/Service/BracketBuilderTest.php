<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\Bracket\GameDto;
use App\Dto\Bracket\StageDto;
use App\Dto\Bracket\TeamResultDto;
use App\Entity\Competition;
use App\Entity\Event;
use App\Entity\Game;
use App\Entity\GameResult;
use App\Entity\Season;
use App\Entity\Team;
use App\Service\BracketBuilder;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BracketBuilderTest extends TestCase
{
    private BracketBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new BracketBuilder();
    }

    #[TestDox('Returns empty bracket when competition has no events')]
    public function testBuildEmptyCompetition(): void
    {
        $competition = $this->createMock(Competition::class);
        $competition->method('getEvents')->willReturn(new ArrayCollection());
        $season = $this->createMock(Season::class);

        $bracket = $this->builder->build($competition, $season);

        $this->assertCount(0, $bracket->stages);
    }

    #[TestDox('Creates stage for event with no games')]
    public function testBuildWithOneEventNoGames(): void
    {
        $event = $this->createEvent(name: 'Quarterfinal', orderIndex: 1, games: []);
        $competition = $this->createCompetition([$event]);
        $season = $this->createMock(Season::class);

        $bracket = $this->builder->build($competition, $season);

        $this->assertCount(1, $bracket->stages);
        $stage = $bracket->stages[0];
        $this->assertInstanceOf(StageDto::class, $stage);
        $this->assertEquals('Quarterfinal', $stage->name);
        $this->assertCount(0, $stage->getGames());
    }

    #[TestDox('Builds correct GameDto with team results')]
    public function testBuildWithGamesAndResults(): void
    {
        $season = $this->createMock(Season::class);

        $game = $this->createGame(id: 42, season: $season, date: new DateTimeImmutable(), gameResults: [
            $this->createGameResult('Team A', 3, true),
            $this->createGameResult('Team B', 1, false),
        ]);

        $event = $this->createEvent(name: 'Semifinal', orderIndex: 1, games: [$game]);
        $competition = $this->createCompetition([$event]);

        $bracket = $this->builder->build($competition, $season);

        $this->assertCount(1, $bracket->stages);
        $stage = $bracket->stages[0];
        $this->assertEquals('Semifinal', $stage->name);
        $this->assertCount(1, $stage->getGames());

        $gameDto = $stage->getGames()[0];
        $this->assertInstanceOf(GameDto::class, $gameDto);
        $this->assertEquals(42, $gameDto->getId());
        $this->assertCount(2, $gameDto->getTeams());

        $teamDtoNames = array_map(fn(TeamResultDto $t) => $t->getTeamName(), $gameDto->getTeams());
        $this->assertEquals(['Team A', 'Team B'], $teamDtoNames);
    }

    #[TestDox('Skips events with null name')]
    public function testEventWithNullNameIsSkipped(): void
    {
        $event = $this->createEvent(name: null, orderIndex: 0, games: []);
        $competition = $this->createCompetition([$event]);
        $season = $this->createMock(Season::class);

        $bracket = $this->builder->build($competition, $season);

        $this->assertCount(0, $bracket->stages);
    }

    #[TestDox('Sorts events by orderIndex ascending')]
    public function testBuildSortsEventsByOrderIndex(): void
    {
        $season = $this->createMock(Season::class);
        $competition = $this->createCompetition([
            $this->createEvent(name: 'Final', orderIndex: 2, games: []),
            $this->createEvent(name: 'Group Stage', orderIndex: 0, games: []),
            $this->createEvent(name: 'Semi Final', orderIndex: 1, games: []),
        ]);

        $bracket = $this->builder->build($competition, $season);

        $stages = $bracket->stages;
        $this->assertEquals('Group Stage', $stages[0]->name);
        $this->assertEquals('Semi Final', $stages[1]->name);
        $this->assertEquals('Final', $stages[2]->name);
    }

    #[TestDox('Skips games from different season')]
    public function testBuildSkipsGamesFromDifferentSeason(): void
    {
        $season = $this->createMock(Season::class);
        $otherSeason = $this->createMock(Season::class);

        $game = $this->createGame(id: 1, season: $otherSeason, date: new DateTimeImmutable(), gameResults: [
            $this->createGameResult('Team A', 1, true),
            $this->createGameResult('Team B', 0, false),
        ]);

        $event = $this->createEvent(name: 'Group Stage', orderIndex: 0, games: [$game]);
        $competition = $this->createCompetition([$event]);

        $bracket = $this->builder->build($competition, $season);

        $this->assertEmpty($bracket->stages[0]->getGames());
    }

    #[TestDox('Skips games with null ID')]
    public function testBuildSkipsGamesWithNullId(): void
    {
        $season = $this->createMock(Season::class);

        $game = $this->createGame(id: null, season: $season, date: new DateTimeImmutable(), gameResults: [
            $this->createGameResult('Team A', 1, true),
            $this->createGameResult('Team B', 0, false),
        ]);

        $event = $this->createEvent(name: 'Group Stage', orderIndex: 0, games: [$game]);
        $competition = $this->createCompetition([$event]);

        $bracket = $this->builder->build($competition, $season);

        $this->assertEmpty($bracket->stages[0]->getGames());
    }

    #[TestDox('Skips games where all team results are incomplete')]
    public function testBuildSkipsGamesWithEmptyTeamResults(): void
    {
        $season = $this->createMock(Season::class);

        $incompleteResult = $this->createMock(GameResult::class);
        $incompleteResult->method('getTeam')->willReturn(null);
        $incompleteResult->method('getMatchScore')->willReturn(null);
        $incompleteResult->method('isWin')->willReturn(false);

        $game = $this->createGame(
            id: 1,
            season: $season,
            date: new DateTimeImmutable(),
            gameResults: [$incompleteResult]
        );
        $event = $this->createEvent(name: 'Group Stage', orderIndex: 0, games: [$game]);
        $competition = $this->createCompetition([$event]);

        $bracket = $this->builder->build($competition, $season);

        $this->assertEmpty($bracket->stages[0]->getGames());
    }

    #[TestDox('Skips game result when matchScore is null')]
    public function testBuildSkipsGameResultWithNullMatchScore(): void
    {
        $season = $this->createMock(Season::class);

        $team = $this->createMock(Team::class);
        $team->method('getName')->willReturn('Team A');

        $nullScoreResult = $this->createMock(GameResult::class);
        $nullScoreResult->method('getTeam')->willReturn($team);
        $nullScoreResult->method('getMatchScore')->willReturn(null);
        $nullScoreResult->method('isWin')->willReturn(false);

        $game = $this->createGame(
            id: 1,
            season: $season,
            date: new DateTimeImmutable(),
            gameResults: [$nullScoreResult]
        );
        $event = $this->createEvent(name: 'Group Stage', orderIndex: 0, games: [$game]);
        $competition = $this->createCompetition([$event]);

        $bracket = $this->builder->build($competition, $season);

        $this->assertEmpty($bracket->stages[0]->getGames());
    }

    #[TestDox('Sorts games by date descending')]
    public function testBuildSortsGamesByDateDescending(): void
    {
        $season = $this->createMock(Season::class);

        $olderGame = $this->createGame(
            id: 1,
            season: $season,
            date: new DateTimeImmutable('2024-01-01'),
            gameResults: [
                $this->createGameResult('Team A', 1, true),
                $this->createGameResult('Team B', 0, false),
            ]
        );

        $newerGame = $this->createGame(
            id: 2,
            season: $season,
            date: new DateTimeImmutable('2024-06-01'),
            gameResults: [
                $this->createGameResult('Team C', 2, true),
                $this->createGameResult('Team D', 1, false),
            ]
        );

        $event = $this->createEvent(name: 'Group Stage', orderIndex: 0, games: [$olderGame, $newerGame]);
        $competition = $this->createCompetition([$event]);

        $bracket = $this->builder->build($competition, $season);

        $games = $bracket->stages[0]->getGames();
        $this->assertEquals(2, $games[0]->getId());
        $this->assertEquals(1, $games[1]->getId());
    }

    #[TestDox('Sorts games by team IDs ascending when dates are equal')]
    public function testBuildSortsGamesByTeamIdsWhenDatesAreEqual(): void
    {
        $season = $this->createMock(Season::class);
        $date = new DateTimeImmutable('2024-03-15');

        // Game z wyższymi ID zespołów
        $gameHighIds = $this->createGameWithTeamIds(id: 1, season: $season, date: $date, teamIds: [10, 20]);

        // Game z niższymi ID zespołów
        $gameLowIds = $this->createGameWithTeamIds(id: 2, season: $season, date: $date, teamIds: [3, 7]);

        $event = $this->createEvent(name: 'Group Stage', orderIndex: 0, games: [$gameHighIds, $gameLowIds]);
        $competition = $this->createCompetition([$event]);

        $bracket = $this->builder->build($competition, $season);

        $games = $bracket->stages[0]->getGames();
        $this->assertEquals(2, $games[0]->getId()); // niższe ID zespołów → pierwsze
        $this->assertEquals(1, $games[1]->getId());
    }

    #[TestDox('Uses 0 as fallback when team ID is null during sort')]
    public function testBuildUsesZeroAsFallbackForNullTeamIdDuringSort(): void
    {
        $season = $this->createMock(Season::class);
        $date = new DateTimeImmutable('2024-03-15');

        // Game z null team ID → traktowane jako 0
        $gameNullTeam = $this->createGameWithTeamIds(id: 1, season: $season, date: $date, teamIds: [null, 5]);

        // Game z ID > 0
        $gameWithIds = $this->createGameWithTeamIds(id: 2, season: $season, date: $date, teamIds: [3, 7]);

        $event = $this->createEvent(name: 'Group Stage', orderIndex: 0, games: [$gameWithIds, $gameNullTeam]);
        $competition = $this->createCompetition([$event]);

        $bracket = $this->builder->build($competition, $season);

        $games = $bracket->stages[0]->getGames();
        $this->assertEquals(1, $games[0]->getId()); // [0,5] < [3,7]
        $this->assertEquals(2, $games[1]->getId());
    }

    // --- Helpers ---

    /**
     * @param array<MockObject&Event> $events
     * @return Competition&MockObject
     */
    private function createCompetition(array $events): MockObject&Competition
    {
        $competition = $this->createMock(Competition::class);
        $competition->method('getEvents')->willReturn(new ArrayCollection($events));

        return $competition;
    }

    /**
     * @param ?string $name
     * @param int $orderIndex
     * @param array<MockObject&Game> $games
     * @return Event&MockObject
     */
    private function createEvent(?string $name, int $orderIndex, array $games): MockObject&Event
    {
        $event = $this->createMock(Event::class);
        $event->method('getName')->willReturn($name);
        $event->method('getOrderIndex')->willReturn($orderIndex);
        $event->method('getGames')->willReturn(new ArrayCollection($games));

        return $event;
    }

    /**
     * @param ?int $id
     * @param Season $season
     * @param ?DateTimeImmutable $date
     * @param array<MockObject&GameResult> $gameResults
     * @return MockObject&Game
     */
    private function createGame(?int $id, Season $season, ?DateTimeImmutable $date, array $gameResults): MockObject&Game
    {
        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn($id);
        $game->method('getSeason')->willReturn($season);
        $game->method('getDate')->willReturn($date);
        $game->method('getGameResults')->willReturn(new ArrayCollection($gameResults));

        return $game;
    }

    private function createGameResult(string $teamName, int $matchScore, bool $isWin): GameResult&MockObject
    {
        $team = $this->createMock(Team::class);
        $team->method('getName')->willReturn($teamName);
        $team->method('getId')->willReturn(rand(1, 100));

        $gameResult = $this->createMock(GameResult::class);
        $gameResult->method('getTeam')->willReturn($team);
        $gameResult->method('getMatchScore')->willReturn($matchScore);
        $gameResult->method('isWin')->willReturn($isWin);

        return $gameResult;
    }

    /**
     * @param ?int $id
     * @param Season $season,
     * @param ?DateTimeImmutable $date,
     * @param array<?int> $teamIds
     * @return Game&MockObject
     */
    private function createGameWithTeamIds(
        ?int $id,
        Season $season,
        ?DateTimeImmutable $date,
        array $teamIds,
    ): Game&MockObject {
        $gameResults = array_map(function (?int $teamId) {
            $team = $this->createMock(Team::class);
            $team->method('getId')->willReturn($teamId);
            $team->method('getName')->willReturn('Team ' . ($teamId ?? 'null'));

            $gameResult = $this->createMock(GameResult::class);
            $gameResult->method('getTeam')->willReturn($teamId !== null ? $team : null);
            $gameResult->method('getMatchScore')->willReturn(1);
            $gameResult->method('isWin')->willReturn(false);

            return $gameResult;
        }, $teamIds);

        return $this->createGame(id: $id, season: $season, date: $date, gameResults: $gameResults);
    }
}
