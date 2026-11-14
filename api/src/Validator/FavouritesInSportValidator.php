<?php

declare(strict_types=1);

namespace App\Validator;

use App\Dto\Request\PreferenceRequest;
use App\Entity\SportScopedInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class FavouritesInSportValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof FavouritesInSport) {
            throw new UnexpectedTypeException($constraint, FavouritesInSport::class);
        }

        if (!$value instanceof PreferenceRequest) {
            return;
        }

        $favouriteSportId = $value->getFavouriteSport()?->getId();

        foreach ($this->sportScopedFavourites($value) as $path => $favourite) {
            if ($favourite === null) {
                continue;
            }

            if ($favouriteSportId === null || $favourite->getSport()?->getId() !== $favouriteSportId) {
                $this->context->buildViolation($constraint->message)
                    ->atPath($path)
                    ->addViolation();
            }
        }
    }

    /**
     * @return array<string, ?SportScopedInterface> keyed by the property path – a new favourite goes here
     */
    private function sportScopedFavourites(PreferenceRequest $request): array
    {
        return [
            'favouriteCompetition' => $request->getFavouriteCompetition(),
            'favouriteTeam' => $request->getFavouriteTeam(),
        ];
    }
}
