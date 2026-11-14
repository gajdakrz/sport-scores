<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Every sport-specific favourite (competition, team) must belong to the favourite sport (and so requires one).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class FavouritesInSport extends Constraint
{
    public string $message = 'It must belong to the favourite sport.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
