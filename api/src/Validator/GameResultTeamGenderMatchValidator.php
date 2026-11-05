<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\GameResult;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class GameResultTeamGenderMatchValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof GameResultTeamGenderMatch) {
            throw new UnexpectedTypeException($constraint, GameResultTeamGenderMatch::class);
        }

        if (!$value instanceof GameResult) {
            return;
        }

        $team = $value->getTeam();
        $competition = $value->getGame()?->getEvent()?->getCompetition();

        if ($team === null || $competition === null || $team->canPlayIn($competition)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ teamGender }}', $team->getGender()->label())
            ->setParameter('{{ competitionGender }}', $competition->getGender()->label())
            ->atPath('team')
            ->addViolation();
    }
}
