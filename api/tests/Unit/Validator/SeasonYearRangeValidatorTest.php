<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Entity\Season;
use App\Validator\SeasonYearRange;
use App\Validator\SeasonYearRangeValidator;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class SeasonYearRangeValidatorTest extends TestCase
{
    private SeasonYearRangeValidator $validator;
    private ExecutionContextInterface&MockObject $context;
    private SeasonYearRange $constraint;

    protected function setUp(): void
    {
        $this->validator = new SeasonYearRangeValidator();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->constraint = new SeasonYearRange();

        $this->validator->initialize($this->context);
    }

    #[TestDox('Throws UnexpectedTypeException when constraint is not SeasonYearRange')]
    public function testThrowsExceptionForInvalidConstraintType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Season(), $this->createMock(Constraint::class));
    }

    #[TestDox('Skips validation when value is not a Season instance')]
    public function testSkipsValidationForNonSeasonValue(): void
    {
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate('not-a-season', $this->constraint);
        $this->validator->validate(null, $this->constraint);
        $this->validator->validate(42, $this->constraint);
    }

    #[TestDox('Does not add violation when endYear equals startYear')]
    public function testNoViolationWhenEndYearEqualsStartYear(): void
    {
        $season = $this->createSeason(startYear: 2024, endYear: 2024);

        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($season, $this->constraint);
    }

    #[TestDox('Does not add violation when endYear is greater than startYear')]
    public function testNoViolationWhenEndYearIsAfterStartYear(): void
    {
        $season = $this->createSeason(startYear: 2023, endYear: 2024);

        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($season, $this->constraint);
    }

    #[TestDox('Adds violation when endYear is before startYear')]
    public function testAddsViolationWhenEndYearIsBeforeStartYear(): void
    {
        $season = $this->createSeason(startYear: 2024, endYear: 2023);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())->method('addViolation');
        $violationBuilder->method('setParameter')->willReturnSelf();

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($violationBuilder);

        $this->validator->validate($season, $this->constraint);
    }

    #[TestDox('Sets startYear and endYear as message parameters')]
    public function testSetsCorrectMessageParameters(): void
    {
        $season = $this->createSeason(startYear: 2025, endYear: 2020);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnMap([
                ['{{ startYear }}', '2025', $violationBuilder],
                ['{{ endYear }}', '2020', $violationBuilder],
            ]);
        $violationBuilder->method('addViolation');

        $this->context
            ->method('buildViolation')
            ->willReturn($violationBuilder);

        $this->validator->validate($season, $this->constraint);
    }

    #[TestDox('Uses default violation message from constraint')]
    public function testUsesDefaultViolationMessage(): void
    {
        $season = $this->createSeason(startYear: 2024, endYear: 2020);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('setParameter')->willReturnSelf();
        $violationBuilder->method('addViolation');

        $this->context
            ->method('buildViolation')
            ->with('End year: {{ endYear }} cannot be before start year: {{ startYear }}.')
            ->willReturn($violationBuilder);

        $this->validator->validate($season, $this->constraint);
    }

    #[TestDox('Uses custom violation message when overridden in constraint')]
    public function testUsesCustomViolationMessage(): void
    {
        $season = $this->createSeason(startYear: 2024, endYear: 2020);

        $customConstraint = new SeasonYearRange();
        $customConstraint->message = 'Custom range error.';

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('setParameter')->willReturnSelf();
        $violationBuilder->method('addViolation');

        $this->context
            ->method('buildViolation')
            ->with('Custom range error.')
            ->willReturn($violationBuilder);

        $this->validator->validate($season, $customConstraint);
    }

    private function createSeason(int $startYear, int $endYear): Season&MockObject
    {
        $season = $this->createMock(Season::class);
        $season->method('getStartYear')->willReturn($startYear);
        $season->method('getEndYear')->willReturn($endYear);

        return $season;
    }
}
