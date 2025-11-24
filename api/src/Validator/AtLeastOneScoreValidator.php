<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\GameResult;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class AtLeastOneScoreValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof AtLeastOneScore) {
            throw new UnexpectedTypeException($constraint, AtLeastOneScore::class);
        }

        if (!$value instanceof GameResult) {
            return;
        }

        if ($value->getMatchScore() === null && $value->getRankingScore() === null) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
