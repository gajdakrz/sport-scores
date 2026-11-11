<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Team;
use App\Enum\GenderCategory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class TeamGenderConsistentValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof TeamGenderConsistent) {
            throw new UnexpectedTypeException($constraint, TeamGenderConsistent::class);
        }

        // A new team has no members, and its gender may still be unset when the form field was empty.
        if (!$value instanceof Team || $value->getTeamMembers()->isEmpty()) {
            return;
        }

        if ($value->getGender() === GenderCategory::MIXED) {
            return;
        }

        foreach ($value->getTeamMembers() as $teamMember) {
            $person = $teamMember->getPerson();

            if (!$teamMember->isActive() || $person === null || $value->acceptsGender($person->getGender())) {
                continue;
            }

            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ teamGender }}', $value->getGender()->label())
                ->atPath('gender')
                ->addViolation();

            return;
        }
    }
}
