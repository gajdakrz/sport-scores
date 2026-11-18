<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Game;
use App\Entity\GameResult;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueGameParticipantsValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueGameParticipants) {
            throw new UnexpectedTypeException($constraint, UniqueGameParticipants::class);
        }

        if (!$value instanceof Game) {
            return;
        }

        $results = $value->getGameResults();
        $teams = $results->map(static fn(GameResult $result) => $result->getTeam())->toArray();
        $persons = $results->map(static fn(GameResult $result) => $result->getPerson())->toArray();

        if ($this->hasDuplicates($teams) || $this->hasDuplicates($persons)) {
            $this->context->buildViolation($constraint->message)
                ->atPath('gameResults')
                ->addViolation();
        }
    }

    /**
     * @param array<?object> $participants results without this participant (null) are skipped
     */
    private function hasDuplicates(array $participants): bool
    {
        $participants = array_filter($participants);

        return count($participants) !== count(array_unique(array_map('spl_object_id', $participants)));
    }
}
