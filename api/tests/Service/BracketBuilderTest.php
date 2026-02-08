<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\Bracket\BracketDto;
use App\Dto\Bracket\StageDto;
use App\Dto\Bracket\GameDto;
use App\Dto\Bracket\TeamResultDto;
use App\Entity\Competition;
use App\Entity\Event;
use App\Entity\Game;
use App\Entity\GameResult;
use App\Entity\Season;
use App\Entity\Team;
use App\Service\BracketBuilder;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class BracketBuilderTest extends TestCase
{
    private BracketBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new BracketBuilder();
    }

    public function testBuildEmptyCompetition(): void
    {
        $competition = $this->createMock(Competition::class);
        $competition->method('getEvents')->willReturn(new ArrayCollection());
        $season = $this->createMock(Season::class);
        $bracket = $this->builder->build($competition, $season);
        $this->assertCount(0, $bracket->stages);
    }

    public function testBuildWithOneEventNoGames(): void
    {
        $event = $this->createMock(Event::class);
        $event->method('getName')->willReturn('Quarterfinal');
        $event->method('getGames')->willReturn(new ArrayCollection());
        $event->method('getOrderIndex')->willReturn(1);

        $competition = $this->createMock(Competition::class);
        $competition->method('getEvents')->willReturn(new ArrayCollection([$event]));

        $season = $this->createMock(Season::class);

        $bracket = $this->builder->build($competition, $season);

        $this->assertCount(1, $bracket->stages);
        $stage = $bracket->stages[0];
        $this->assertInstanceOf(StageDto::class, $stage);
        $this->assertEquals('Quarterfinal', $stage->name); // <--- public property
        $this->assertCount(0, $stage->getGames());
    }

    public function testBuildWithGamesAndResults(): void
    {
        $team1 = $this->createMock(Team::class);
        $team1->method('getName')->willReturn('Team A');

        $team2 = $this->createMock(Team::class);
        $team2->method('getName')->willReturn('Team B');

        $gameResult1 = $this->createMock(GameResult::class);
        $gameResult1->method('getTeam')->willReturn($team1);
        $gameResult1->method('getMatchScore')->willReturn(3);
        $gameResult1->method('isWin')->willReturn(true);

        $gameResult2 = $this->createMock(GameResult::class);
        $gameResult2->method('getTeam')->willReturn($team2);
        $gameResult2->method('getMatchScore')->willReturn(1);
        $gameResult2->method('isWin')->willReturn(false);

        $season = $this->createMock(Season::class);

        $game = $this->createMock(Game::class);
        $game->method('getId')->willReturn(42);
        $game->method('getSeason')->willReturn($season);
        $game->method('getGameResults')->willReturn(new ArrayCollection([$gameResult1, $gameResult2]));

        $event = $this->createMock(Event::class);
        $event->method('getName')->willReturn('Semifinal');
        $event->method('getGames')->willReturn(new ArrayCollection([$game]));
        $event->method('getOrderIndex')->willReturn(1);

        $competition = $this->createMock(Competition::class);
        $competition->method('getEvents')->willReturn(new ArrayCollection([$event]));

        $bracket = $this->builder->build($competition, $season);

        $this->assertCount(1, $bracket->stages);

        $stage = $bracket->stages[0];
        $this->assertEquals('Semifinal', $stage->name); // <--- public property
        $this->assertCount(1, $stage->getGames());

        $gameDto = $stage->getGames()[0];
        $this->assertInstanceOf(GameDto::class, $gameDto);
        $this->assertEquals(42, $gameDto->getId());
        $this->assertCount(2, $gameDto->getTeams());

        $teamDtoNames = array_map(fn(TeamResultDto $t) => $t->getTeamName(), $gameDto->getTeams());
        $this->assertEquals(['Team A', 'Team B'], $teamDtoNames);
    }

    public function testEventWithNullNameIsSkipped(): void
    {
        $event = $this->createMock(Event::class);
        $event->method('getName')->willReturn(null);
        $event->method('getGames')->willReturn(new ArrayCollection());
        $event->method('getOrderIndex')->willReturn(0);

        $competition = $this->createMock(Competition::class);
        $competition->method('getEvents')->willReturn(new ArrayCollection([$event]));

        $season = $this->createMock(Season::class);

        $bracket = $this->builder->build($competition, $season);

        $this->assertCount(0, $bracket->stages);
    }
}
