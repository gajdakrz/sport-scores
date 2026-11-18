<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class GameResultTeamCountryMatch extends Constraint
{
    public string $message =
        'Team country "{{ teamCountry }}" does not match competition country "{{ competitionCountry }}".';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
