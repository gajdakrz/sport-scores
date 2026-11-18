<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class TeamCompetitionCountryAllowed extends Constraint
{
    public string $message =
        'A national team plays only international competitions – leave the competition country empty.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
