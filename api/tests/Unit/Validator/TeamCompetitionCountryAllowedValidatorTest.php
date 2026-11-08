<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Entity\Country;
use App\Entity\Team;
use App\Enum\TeamType;
use App\Validator\TeamCompetitionCountryAllowed;
use App\Validator\TeamCompetitionCountryAllowedValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class TeamCompetitionCountryAllowedValidatorTest extends TestCase
{
    #[Test]
    #[TestDox('National team with a competition country gets a violation on "competitionCountry"')]
    public function nationalTeamWithCompetitionCountryAddsViolation(): void
    {
        $team = (new Team())
            ->setTeamType(TeamType::NATIONAL)
            ->setCompetitionCountry((new Country())->setName('Poland'));

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())->method('atPath')->with('competitionCountry')->willReturnSelf();
        $builder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())->method('buildViolation')->willReturn($builder);

        $this->validate($context, $team);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function acceptedValueProvider(): iterable
    {
        $poland = (new Country())->setName('Poland');

        yield 'national team without a competition country' => [(new Team())->setTeamType(TeamType::NATIONAL)];
        yield 'club with a competition country' => [
            (new Team())->setTeamType(TeamType::CLUB)->setCompetitionCountry($poland),
        ];
        // new team from an empty or invalid form field: team type not set yet
        yield 'team without a type' => [(new Team())->setCompetitionCountry($poland)];
        yield 'not a team' => [null];
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

        (new TeamCompetitionCountryAllowedValidator())->validate(null, $this->createStub(Constraint::class));
    }

    private function validate(ExecutionContextInterface $context, mixed $value): void
    {
        $validator = new TeamCompetitionCountryAllowedValidator();
        $validator->initialize($context);
        $validator->validate($value, new TeamCompetitionCountryAllowed());
    }
}
