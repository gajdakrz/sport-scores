<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class TeamCompetitionCountryAllowed extends Constraint
{
    public string $message =
        'A national team plays only international competitions – leave the competition country empty.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
