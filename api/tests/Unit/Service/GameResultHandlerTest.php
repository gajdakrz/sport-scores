<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Game;
use App\Entity\GameResult;
use App\Entity\User;
use App\Service\GameResultHandler;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class GameResultHandlerTest extends TestCase
{
    private GameResultHandler $handler;
    private User $user;

    protected function setUp(): void
    {
        $this->handler = new GameResultHandler();
        $this->user = $this->createMock(User::class);
    }

    public function testHandleSetsCreatedByAndModifiedByForNewGameResult(): void
    {
        $gameResult = $this->createMock(GameResult::class);
        $gameResult->method('getId')->willReturn(null);
        $gameResult->expects($this->once())->method('setCreatedBy')->with($this->user);
        $gameResult->expects($this->once())->method('setModifiedBy')->with($this->user);

        $game = $this->createGameWithResults([$gameResult]);

        $this->handler->handle($game, $this->user);
    }

    public function testHandleSetsOnlyModifiedByForExistingGameResult(): void
    {
        $gameResult = $this->createMock(GameResult::class);
        $gameResult->method('getId')->willReturn(1);
        $gameResult->expects($this->never())->method('setCreatedBy');
        $gameResult->expects($this->once())->method('setModifiedBy')->with($this->user);

        $game = $this->createGameWithResults([$gameResult]);

        $this->handler->handle($game, $this->user);
    }

    public function testHandleDoesNothingWhenNoGameResults(): void
    {
        $game = $this->createGameWithResults([]);
        $this->handler->handle($game, $this->user);
        $this->expectNotToPerformAssertions();
    }

    public function testHandleMixedNewAndExistingGameResults(): void
    {
        $newResult = $this->createMock(GameResult::class);
        $newResult->method('getId')->willReturn(null);
        $newResult->expects($this->once())->method('setCreatedBy')->with($this->user);
        $newResult->expects($this->once())->method('setModifiedBy')->with($this->user);

        $existingResult = $this->createMock(GameResult::class);
        $existingResult->method('getId')->willReturn(5);
        $existingResult->expects($this->never())->method('setCreatedBy');
        $existingResult->expects($this->once())->method('setModifiedBy')->with($this->user);

        $game = $this->createGameWithResults([$newResult, $existingResult]);

        $this->handler->handle($game, $this->user);
    }


    /**
     * @param GameResult[] $results
     * @return Game
     */
    private function createGameWithResults(array $results): Game
    {
        $game = $this->createMock(Game::class);
        $game->method('getGameResults')->willReturn(new ArrayCollection($results));

        return $game;
    }
}
