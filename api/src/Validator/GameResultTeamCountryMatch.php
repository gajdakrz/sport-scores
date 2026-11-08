<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class GameResultTeamCountryMatch extends Constraint
{
    public string $message =
        'Team country "{{ teamCountry }}" does not match competition country "{{ competitionCountry }}".';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
