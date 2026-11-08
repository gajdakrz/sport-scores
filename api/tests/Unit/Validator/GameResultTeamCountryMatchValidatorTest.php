<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Entity\Competition;
use App\Entity\Country;
use App\Entity\Event;
use App\Entity\Game;
use App\Entity\GameResult;
use App\Entity\Team;
use App\Validator\GameResultTeamCountryMatch;
use App\Validator\GameResultTeamCountryMatchValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class GameResultTeamCountryMatchValidatorTest extends TestCase
{
    /**
     * @return iterable<string, array{GameResult}>
     */
    public static function rejectedGameResultProvider(): iterable
    {
        $poland  = self::country('Poland');
        $germany = self::country('Germany');

        yield 'team playing in another country' => [self::gameResult($poland, self::team($poland, $germany))];
        // no fallback to the country of origin: an empty competition country means "not applicable"
        yield 'national team (no competition country)' => [self::gameResult($poland, self::team($poland, null))];
    }

    #[Test]
    #[DataProvider('rejectedGameResultProvider')]
    #[TestDox('National competition rejects a team on "team" path: $_dataName')]
    public function nationalCompetitionRejectsTeamPlayingElsewhere(GameResult $gameResult): void
    {
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->method('setParameter')->willReturnSelf();
        $builder->expects($this->once())->method('atPath')->with('team')->willReturnSelf();
        $builder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())->method('buildViolation')->willReturn($builder);

        $this->validate($context, $gameResult);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function acceptedValueProvider(): iterable
    {
        $poland  = self::country('Poland');
        $germany = self::country('Germany');
        $monaco  = self::country('Monaco');

        yield 'national competition, team playing in its country' => [
            self::gameResult($poland, self::team($poland, $poland)),
        ];
        yield 'national competition, foreign-origin team playing in its country (like AS Monaco)' => [
            self::gameResult($poland, self::team($monaco, $poland)),
        ];
        yield 'international competition, team playing elsewhere' => [
            self::gameResult(null, self::team($germany, $germany)),
        ];
        yield 'international competition, national team' => [self::gameResult(null, self::team($poland, null))];
        yield 'no team selected' => [self::gameResult($poland, null)];
        yield 'no competition' => [(new GameResult())->setTeam(self::team($germany, $germany))];
        yield 'not a game result' => [null];
    }

    #[Test]
    #[DataProvider('acceptedValueProvider')]
    #[TestDox('No violation: $_dataName')]
    public function acceptedValuesAddNoViolation(mixed $value): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $this->validate($context, $value);
    }

    #[Test]
    #[TestDox('Throws UnexpectedTypeException for a foreign constraint')]
    public function rejectsForeignConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        (new GameResultTeamCountryMatchValidator())->validate(null, $this->createStub(Constraint::class));
    }

    private function validate(ExecutionContextInterface $context, mixed $value): void
    {
        $validator = new GameResultTeamCountryMatchValidator();
        $validator->initialize($context);
        $validator->validate($value, new GameResultTeamCountryMatch());
    }

    private static function country(string $name): Country
    {
        return (new Country())->setName($name);
    }

    private static function team(?Country $country, ?Country $competitionCountry): Team
    {
        return (new Team())->setCountry($country)->setCompetitionCountry($competitionCountry);
    }

    private static function gameResult(?Country $competitionCountry, ?Team $team): GameResult
    {
        $competition = (new Competition())->setCountry($competitionCountry);
        $game        = (new Game())->setEvent((new Event())->setCompetition($competition));

        return (new GameResult())->setGame($game)->setTeam($team);
    }
}
