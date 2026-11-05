<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\TeamMember;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class TeamMemberGenderMatchValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof TeamMemberGenderMatch) {
            throw new UnexpectedTypeException($constraint, TeamMemberGenderMatch::class);
        }

        if (!$value instanceof TeamMember) {
            return;
        }

        $team = $value->getTeam();
        $person = $value->getPerson();

        if ($team === null || $person === null || $team->acceptsGender($person->getGender())) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ personGender }}', $person->getGender()->label())
            ->setParameter('{{ teamGender }}', $team->getGender()->label())
            ->atPath('person')
            ->addViolation();
    }
}
