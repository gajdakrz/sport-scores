<?php

declare(strict_types=1);

namespace App\Service\Preference;

use App\Entity\Sport;
use App\Entity\UserPreference;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Turns one user preference into default query parameters of the list routes it applies to.
 * A new preference that presets a filter = one more implementation; it is picked up automatically.
 */
#[AutoconfigureTag]
interface FilterDefaultsProviderInterface
{
    /**
     * @param ?UserPreference $preference null when nobody is logged in (the app's defaults apply)
     *
     * @return array<string, int|string> empty when the route is not affected
     */
    public function getDefaults(string $route, ?UserPreference $preference, ?Sport $currentSport): array;
}
