<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class TeamMemberGenderMatch extends Constraint
{
    public string $message = 'Person gender "{{ personGender }}" does not match team gender "{{ teamGender }}".';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
