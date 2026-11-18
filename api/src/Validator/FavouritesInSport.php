<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Every sport-specific favourite (competition, team) must belong to the favourite sport (and so requires one).
 */
#[Attribute]
class FavouritesInSport extends Constraint
{
    public string $message = 'It must belong to the favourite sport.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
