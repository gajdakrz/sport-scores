<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Team;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class TeamCompetitionCountryAllowedValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof TeamCompetitionCountryAllowed) {
            throw new UnexpectedTypeException($constraint, TeamCompetitionCountryAllowed::class);
        }

        if (!$value instanceof Team || !$value->isNational() || $value->getCompetitionCountry() === null) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->atPath('competitionCountry')
            ->addViolation();
    }
}
