<?php

declare(strict_types=1);

namespace App\Service\Preference;

use App\Entity\Sport;
use App\Entity\UserPreference;

/**
 * Lists with a competition filter open filtered by the favourite competition, while its sport is the current one.
 */
final readonly class FavouriteCompetitionFilterDefaults implements FilterDefaultsProviderInterface
{
    private const array ROUTES = ['game_index', 'event_index', 'team_game_result_season_index'];

    public function getDefaults(string $route, ?UserPreference $preference, ?Sport $currentSport): array
    {
        if (!in_array($route, self::ROUTES, true)) {
            return [];
        }

        $competitionId = $preference?->getFavouriteCompetitionFor($currentSport)?->getId();

        return $competitionId === null ? [] : ['competitionId' => $competitionId];
    }
}
