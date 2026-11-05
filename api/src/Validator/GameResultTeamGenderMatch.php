<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class GameResultTeamGenderMatch extends Constraint
{
    public string $message =
        'Team gender "{{ teamGender }}" does not match competition gender "{{ competitionGender }}".';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
