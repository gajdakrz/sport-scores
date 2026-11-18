<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * A team or a person may have only one result in a game. Checks the results submitted together in the game form –
 * they are not in the database yet, so UniqueEntity on GameResult can't compare them with each other.
 */
#[Attribute]
class UniqueGameParticipants extends Constraint
{
    public string $message = 'A team or a person can have only one result in a game.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
