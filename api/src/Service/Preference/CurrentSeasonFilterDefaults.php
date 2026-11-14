<?php

declare(strict_types=1);

namespace App\Service\Preference;

use App\Entity\Sport;
use App\Entity\UserPreference;

/**
 * Games open with the "Current season" filter on, unless the user switched it off in the preferences.
 */
final readonly class CurrentSeasonFilterDefaults implements FilterDefaultsProviderInterface
{
    public function getDefaults(string $route, ?UserPreference $preference, ?Sport $currentSport): array
    {
        if ($route !== 'game_index' || !($preference?->isCurrentSeasonByDefault() ?? true)) {
            return [];
        }

        return ['currentSeason' => 1];
    }
}
