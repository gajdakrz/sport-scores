<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class TeamGenderConsistent extends Constraint
{
    public string $message = 'Team has active members of a different gender than "{{ teamGender }}".';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
