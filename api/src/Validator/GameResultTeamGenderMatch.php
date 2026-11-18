<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class GameResultTeamGenderMatch extends Constraint
{
    public string $message =
        'Team gender "{{ teamGender }}" does not match competition gender "{{ competitionGender }}".';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
