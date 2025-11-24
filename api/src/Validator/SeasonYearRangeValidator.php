<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Season;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class SeasonYearRangeValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SeasonYearRange) {
            throw new UnexpectedTypeException($constraint, SeasonYearRange::class);
        }

        if (!$value instanceof Season) {
            return;
        }

        if ($value->getEndYear() < $value->getStartYear()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ startYear }}', (string)$value->getStartYear())
                ->setParameter('{{ endYear }}', (string)$value->getEndYear())
                ->addViolation();
        }
    }
}
