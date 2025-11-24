<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class SeasonYearRange extends Constraint
{
    public string $message = 'End year: {{ endYear }} cannot be before start year: {{ startYear }}.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
