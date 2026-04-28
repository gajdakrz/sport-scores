<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\MatchStat;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

class MatchStatTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Konstruktor – wartości domyślne
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Default constructor initializes all fields to zero')]
    public function defaultConstructorInitializesAllFieldsToZero(): void
    {
        $stat = new MatchStat();

        self::assertSame(0, $stat->total);
        self::assertSame(0, $stat->wins);
        self::assertSame(0, $stat->losses);
        self::assertSame(0, $stat->draws);
        self::assertSame(0, $stat->unknowns);
        self::assertSame(0, $stat->points);
    }

    // -------------------------------------------------------------------------
    // Niemutowalność
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('addWin returns a new instance and does not modify the original')]
    public function addWinReturnsNewInstance(): void
    {
        $original = new MatchStat();
        $result   = $original->addWin();

        self::assertNotSame($original, $result);
        self::assertSame(0, $original->total);
    }

    #[Test]
    #[TestDox('addLoss returns a new instance and does not modify the original')]
    public function addLossReturnsNewInstance(): void
    {
        $original = new MatchStat();
        $result   = $original->addLoss();

        self::assertNotSame($original, $result);
        self::assertSame(0, $original->total);
    }

    #[Test]
    #[TestDox('addDraw returns a new instance and does not modify the original')]
    public function addDrawReturnsNewInstance(): void
    {
        $original = new MatchStat();
        $result   = $original->addDraw();

        self::assertNotSame($original, $result);
        self::assertSame(0, $original->total);
    }

    #[Test]
    #[TestDox('addUnknown returns a new instance and does not modify the original')]
    public function addUnknownReturnsNewInstance(): void
    {
        $original = new MatchStat();
        $result   = $original->addUnknown();

        self::assertNotSame($original, $result);
        self::assertSame(0, $original->total);
    }

    // -------------------------------------------------------------------------
    // addWin
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('addWin increments total and wins by 1 and adds 3 points')]
    public function addWinIncrementsCorrectFields(): void
    {
        $result = (new MatchStat())->addWin();

        self::assertSame(1, $result->total);
        self::assertSame(1, $result->wins);
        self::assertSame(0, $result->losses);
        self::assertSame(0, $result->draws);
        self::assertSame(0, $result->unknowns);
        self::assertSame(3, $result->points); // MatchPoint::WIN->points() = 3
    }

    // -------------------------------------------------------------------------
    // addLoss
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('addLoss increments total and losses by 1 and adds 0 points')]
    public function addLossIncrementsCorrectFields(): void
    {
        $result = (new MatchStat())->addLoss();

        self::assertSame(1, $result->total);
        self::assertSame(0, $result->wins);
        self::assertSame(1, $result->losses);
        self::assertSame(0, $result->draws);
        self::assertSame(0, $result->unknowns);
        self::assertSame(0, $result->points); // MatchPoint::LOSS->points() = 0
    }

    // -------------------------------------------------------------------------
    // addDraw
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('addDraw increments total and draws by 1 and adds 1 point')]
    public function addDrawIncrementsCorrectFields(): void
    {
        $result = (new MatchStat())->addDraw();

        self::assertSame(1, $result->total);
        self::assertSame(0, $result->wins);
        self::assertSame(0, $result->losses);
        self::assertSame(1, $result->draws);
        self::assertSame(0, $result->unknowns);
        self::assertSame(1, $result->points); // MatchPoint::DRAW->points() = 1
    }

    // -------------------------------------------------------------------------
    // addUnknown
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('addUnknown increments total and unknowns by 1 and adds 0 points')]
    public function addUnknownIncrementsCorrectFields(): void
    {
        $result = (new MatchStat())->addUnknown();

        self::assertSame(1, $result->total);
        self::assertSame(0, $result->wins);
        self::assertSame(0, $result->losses);
        self::assertSame(0, $result->draws);
        self::assertSame(1, $result->unknowns);
        self::assertSame(0, $result->points); // MatchPoint::UNKNOWN->points() = 0
    }

    // -------------------------------------------------------------------------
    // winRate
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('winRate returns 0.0 when total is zero to avoid division by zero')]
    public function winRateReturnsZeroWhenTotalIsZero(): void
    {
        self::assertSame(0.0, (new MatchStat())->winRate());
    }

    #[Test]
    #[TestDox('winRate returns 100.0 when all matches are wins')]
    public function winRateReturnsHundredWhenAllWins(): void
    {
        $stat = (new MatchStat())->addWin()->addWin()->addWin();

        self::assertSame(100.0, $stat->winRate());
    }

    #[Test]
    #[TestDox('winRate returns 0.0 when there are no wins')]
    public function winRateReturnsZeroWhenNoWins(): void
    {
        $stat = (new MatchStat())->addLoss()->addDraw()->addUnknown();

        self::assertSame(0.0, $stat->winRate());
    }

    #[Test]
    #[TestDox('winRate returns correctly rounded percentage')]
    public function winRateReturnsCorrectlyRoundedPercentage(): void
    {
        // 1 win out of 3 = 33.33%
        $stat = (new MatchStat())->addWin()->addLoss()->addLoss();

        self::assertSame(33.33, $stat->winRate());
    }

    #[Test]
    #[TestDox('winRate returns 50.0 when half the matches are wins')]
    public function winRateReturnsFiftyPercentForHalfWins(): void
    {
        $stat = (new MatchStat())->addWin()->addLoss();

        self::assertSame(50.0, $stat->winRate());
    }

    // -------------------------------------------------------------------------
    // Akumulacja – łańcuchowanie wywołań
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Chaining multiple add methods accumulates all fields correctly')]
    public function chainingMultipleAddMethodsAccumulatesCorrectly(): void
    {
        $stat = (new MatchStat())
            ->addWin()    // total=1, wins=1, points=3
            ->addWin()    // total=2, wins=2, points=6
            ->addLoss()   // total=3, losses=1, points=6
            ->addDraw()   // total=4, draws=1, points=7
            ->addUnknown(); // total=5, unknowns=1, points=7

        self::assertSame(5, $stat->total);
        self::assertSame(2, $stat->wins);
        self::assertSame(1, $stat->losses);
        self::assertSame(1, $stat->draws);
        self::assertSame(1, $stat->unknowns);
        self::assertSame(7, $stat->points);
        self::assertSame(40.0, $stat->winRate()); // 2/5 = 40%
    }
}
