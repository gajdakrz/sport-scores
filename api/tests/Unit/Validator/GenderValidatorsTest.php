<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Entity\Competition;
use App\Entity\Event;
use App\Entity\Game;
use App\Entity\GameResult;
use App\Entity\Person;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Enum\Gender;
use App\Enum\GenderCategory;
use App\Validator\GameResultTeamGenderMatch;
use App\Validator\GameResultTeamGenderMatchValidator;
use App\Validator\TeamGenderConsistent;
use App\Validator\TeamGenderConsistentValidator;
use App\Validator\TeamMemberGenderMatch;
use App\Validator\TeamMemberGenderMatchValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class GenderValidatorsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // TeamMemberGenderMatch
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('TeamMemberGenderMatch: adds violation on "person" when person gender differs from team gender')]
    public function teamMemberMismatchAddsViolation(): void
    {
        $member = (new TeamMember())
            ->setTeam($this->team(GenderCategory::FEMALE))
            ->setPerson($this->person(Gender::MALE));

        $validator = new TeamMemberGenderMatchValidator();

        $this->assertViolationAt('person', $validator, $member, new TeamMemberGenderMatch());
    }

    #[Test]
    #[TestDox('TeamMemberGenderMatch: no violation for matching gender, mixed team or incomplete data')]
    public function teamMemberValidCasesAddNoViolation(): void
    {
        $validator  = new TeamMemberGenderMatchValidator();
        $constraint = new TeamMemberGenderMatch();

        $this->assertNoViolation($validator, (new TeamMember())
            ->setTeam($this->team(GenderCategory::MALE))
            ->setPerson($this->person(Gender::MALE)), $constraint);
        $this->assertNoViolation($validator, (new TeamMember())
            ->setTeam($this->team(GenderCategory::MIXED))
            ->setPerson($this->person(Gender::FEMALE)), $constraint);
        $withoutPerson = (new TeamMember())->setTeam($this->team(GenderCategory::MALE));
        $this->assertNoViolation($validator, $withoutPerson, $constraint);
        $this->assertNoViolation($validator, 'not-a-team-member', $constraint);
    }

    // -------------------------------------------------------------------------
    // GameResultTeamGenderMatch
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('GameResultTeamGenderMatch: adds violation on "team" when team and competition categories differ')]
    public function gameResultMismatchAddsViolation(): void
    {
        $validator  = new GameResultTeamGenderMatchValidator();
        $constraint = new GameResultTeamGenderMatch();

        $maleInFemale = $this->gameResult(GenderCategory::FEMALE, $this->team(GenderCategory::MALE));
        $this->assertViolationAt('team', $validator, $maleInFemale, $constraint);

        $mixedInFemale = $this->gameResult(GenderCategory::FEMALE, $this->team(GenderCategory::MIXED));
        $this->assertViolationAt('team', $validator, $mixedInFemale, $constraint);
    }

    #[Test]
    #[TestDox('GameResultTeamGenderMatch: no violation for the same category, no team or no competition')]
    public function gameResultValidCasesAddNoViolation(): void
    {
        $validator  = new GameResultTeamGenderMatchValidator();
        $constraint = new GameResultTeamGenderMatch();
        $maleTeam   = $this->team(GenderCategory::MALE);

        $this->assertNoViolation($validator, $this->gameResult(GenderCategory::MALE, $maleTeam), $constraint);
        $mixedInMixed = $this->gameResult(GenderCategory::MIXED, $this->team(GenderCategory::MIXED));
        $this->assertNoViolation($validator, $mixedInMixed, $constraint);
        $this->assertNoViolation($validator, $this->gameResult(GenderCategory::FEMALE, null), $constraint);
        $this->assertNoViolation($validator, (new GameResult())->setTeam($maleTeam), $constraint);
        $this->assertNoViolation($validator, null, $constraint);
    }

    // -------------------------------------------------------------------------
    // TeamGenderConsistent
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('TeamGenderConsistent: adds violation on "gender" when an active member has a different gender')]
    public function teamWithConflictingActiveMemberAddsViolation(): void
    {
        $team = $this->team(GenderCategory::MALE);
        $team->addTeamMember((new TeamMember())->setPerson($this->person(Gender::FEMALE)));

        $this->assertViolationAt('gender', new TeamGenderConsistentValidator(), $team, new TeamGenderConsistent());
    }

    #[Test]
    #[TestDox('TeamGenderConsistent: no violation for mixed team, matching, inactive or no members')]
    public function teamValidCasesAddNoViolation(): void
    {
        $validator  = new TeamGenderConsistentValidator();
        $constraint = new TeamGenderConsistent();

        $mixed = $this->team(GenderCategory::MIXED);
        $mixed->addTeamMember((new TeamMember())->setPerson($this->person(Gender::FEMALE)));
        $this->assertNoViolation($validator, $mixed, $constraint);

        $matching = $this->team(GenderCategory::FEMALE);
        $matching->addTeamMember((new TeamMember())->setPerson($this->person(Gender::FEMALE)));
        $this->assertNoViolation($validator, $matching, $constraint);

        $inactive = $this->team(GenderCategory::MALE);
        $inactive->addTeamMember((new TeamMember())->setPerson($this->person(Gender::FEMALE))->setIsActive(false));
        $this->assertNoViolation($validator, $inactive, $constraint);

        // new team from an empty form field: gender not set yet, no members to check
        $this->assertNoViolation($validator, new Team(), $constraint);
    }

    // -------------------------------------------------------------------------
    // Shared
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('All gender validators throw UnexpectedTypeException for a foreign constraint')]
    public function validatorsRejectForeignConstraint(): void
    {
        $validators = [
            new TeamMemberGenderMatchValidator(),
            new GameResultTeamGenderMatchValidator(),
            new TeamGenderConsistentValidator(),
        ];

        foreach ($validators as $validator) {
            try {
                $validator->validate(null, $this->createStub(Constraint::class));
                $this->fail(sprintf('%s accepted a foreign constraint', $validator::class));
            } catch (UnexpectedTypeException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    private function person(Gender $gender): Person
    {
        return (new Person())->setGender($gender);
    }

    private function team(GenderCategory $gender): Team
    {
        return (new Team())->setGender($gender);
    }

    private function gameResult(GenderCategory $competitionGender, ?Team $team): GameResult
    {
        $competition = (new Competition())->setGender($competitionGender);
        $game        = (new Game())->setEvent((new Event())->setCompetition($competition));

        return (new GameResult())->setGame($game)->setTeam($team);
    }

    private function assertViolationAt(
        string $path,
        ConstraintValidator $validator,
        mixed $value,
        Constraint $constraint
    ): void {
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->method('setParameter')->willReturnSelf();
        $builder->expects($this->once())->method('atPath')->with($path)->willReturnSelf();
        $builder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())->method('buildViolation')->willReturn($builder);

        $validator->initialize($context);
        $validator->validate($value, $constraint);
    }

    private function assertNoViolation(ConstraintValidator $validator, mixed $value, Constraint $constraint): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $validator->initialize($context);
        $validator->validate($value, $constraint);
    }
}
