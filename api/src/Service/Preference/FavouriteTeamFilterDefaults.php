<?php

declare(strict_types=1);

namespace App\Service\Preference;

use App\Entity\Sport;
use App\Entity\Team;
use App\Entity\UserPreference;

/**
 * Lists with a team filter open filtered by the favourite team, while its sport is the current one:
 * by id where the filter is a team select, by name on Teams (which filters by name).
 */
final readonly class FavouriteTeamFilterDefaults implements FilterDefaultsProviderInterface
{
    private const array TEAM_ID_ROUTES = ['game_result_index', 'team_member_index'];
    private const string TEAMS_ROUTE = 'team_index';

    public function getDefaults(string $route, ?UserPreference $preference, ?Sport $currentSport): array
    {
        if ($route !== self::TEAMS_ROUTE && !in_array($route, self::TEAM_ID_ROUTES, true)) {
            return [];
        }

        $team = $preference?->getFavouriteTeamFor($currentSport);
        if ($team === null) {
            return [];
        }

        return $route === self::TEAMS_ROUTE ? $this->byName($team) : $this->byId($team);
    }

    /**
     * @return array<string, string>
     */
    private function byName(Team $team): array
    {
        $name = $team->getName();

        return $name === null || $name === '' ? [] : ['name' => $name];
    }

    /**
     * @return array<string, int>
     */
    private function byId(Team $team): array
    {
        $id = $team->getId();

        return $id === null ? [] : ['teamId' => $id];
    }
}
