<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class AtLeastOneScore extends Constraint
{
    public string $message = 'At least score: "matchScore" or "rankingScore" should be set.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
