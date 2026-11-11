<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\GameResult;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class GameResultTeamCountryMatchValidator extends ConstraintValidator
{
    private const NO_COUNTRY = 'none';

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof GameResultTeamCountryMatch) {
            throw new UnexpectedTypeException($constraint, GameResultTeamCountryMatch::class);
        }

        if (!$value instanceof GameResult) {
            return;
        }

        $team = $value->getTeam();
        $competition = $value->getGame()?->getEvent()?->getCompetition();

        if ($team === null || $competition === null || $competition->admitsTeam($team)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ teamCountry }}', $team->getCompetitionCountry()?->getName() ?? self::NO_COUNTRY)
            ->setParameter('{{ competitionCountry }}', $competition->getCountry()?->getName() ?? self::NO_COUNTRY)
            ->atPath('team')
            ->addViolation();
    }
}
