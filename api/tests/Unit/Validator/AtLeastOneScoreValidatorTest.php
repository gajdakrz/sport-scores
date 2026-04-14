<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Entity\GameResult;
use App\Validator\AtLeastOneScore;
use App\Validator\AtLeastOneScoreValidator;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class AtLeastOneScoreValidatorTest extends TestCase
{
    private AtLeastOneScoreValidator $validator;
    private ExecutionContextInterface&MockObject $context;
    private AtLeastOneScore $constraint;

    protected function setUp(): void
    {
        $this->validator = new AtLeastOneScoreValidator();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->constraint = new AtLeastOneScore();
        $this->validator->initialize($this->context);
    }

    #[TestDox('Throws UnexpectedTypeException when constraint is not AtLeastOneScore')]
    public function testThrowsExceptionForInvalidConstraintType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new GameResult(), $this->createMock(Constraint::class));
    }

    #[TestDox('Skips validation when value is not a GameResult instance')]
    public function testSkipsValidationForNonGameResultValue(): void
    {
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate('not-a-game-result', $this->constraint);
        $this->validator->validate(null, $this->constraint);
        $this->validator->validate(42, $this->constraint);
    }

    #[TestDox('Adds violation when both matchScore and rankingScore are null')]
    public function testAddsViolationWhenBothScoresAreNull(): void
    {
        $gameResult = $this->createGameResult(matchScore: null, rankingScore: null);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($violationBuilder);

        $this->validator->validate($gameResult, $this->constraint);
    }

    #[TestDox('Does not add violation when matchScore is set')]
    public function testNoViolationWhenMatchScoreIsSet(): void
    {
        $gameResult = $this->createGameResult(matchScore: 3, rankingScore: null);

        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($gameResult, $this->constraint);
    }

    #[TestDox('Does not add violation when rankingScore is set')]
    public function testNoViolationWhenRankingScoreIsSet(): void
    {
        $gameResult = $this->createGameResult(matchScore: null, rankingScore: 10);

        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($gameResult, $this->constraint);
    }

    #[TestDox('Does not add violation when both scores are set')]
    public function testNoViolationWhenBothScoresAreSet(): void
    {
        $gameResult = $this->createGameResult(matchScore: 2, rankingScore: 5);

        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($gameResult, $this->constraint);
    }

    #[TestDox('Uses default violation message from constraint')]
    public function testUsesDefaultViolationMessage(): void
    {
        $gameResult = $this->createGameResult(matchScore: null, rankingScore: null);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('addViolation');

        $this->context
            ->method('buildViolation')
            ->with('At least score: "matchScore" or "rankingScore" should be set.')
            ->willReturn($violationBuilder);

        $this->validator->validate($gameResult, $this->constraint);
    }

    #[TestDox('Uses custom violation message when overridden in constraint')]
    public function testUsesCustomViolationMessage(): void
    {
        $gameResult = $this->createGameResult(matchScore: null, rankingScore: null);

        $customConstraint = new AtLeastOneScore();
        $customConstraint->message = 'Custom error message.';

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('addViolation');

        $this->context
            ->method('buildViolation')
            ->with('Custom error message.')
            ->willReturn($violationBuilder);

        $this->validator->validate($gameResult, $customConstraint);
    }

    private function createGameResult(?int $matchScore, ?int $rankingScore): GameResult&MockObject
    {
        $gameResult = $this->createMock(GameResult::class);
        $gameResult->method('getMatchScore')->willReturn($matchScore);
        $gameResult->method('getRankingScore')->willReturn($rankingScore);

        return $gameResult;
    }
}
