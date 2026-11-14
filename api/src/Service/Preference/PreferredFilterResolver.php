<?php

declare(strict_types=1);

namespace App\Service\Preference;

use App\Entity\User;
use App\Entity\UserPreference;
use App\Repository\UserPreferenceRepository;
use App\Service\CurrentSportProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Builds links to list pages with the filters preset from the logged-in user's preferences.
 * The list routes themselves stay unfiltered, so "Clear" (route without query) drops the defaults too.
 */
final class PreferredFilterResolver implements ResetInterface
{
    private ?UserPreference $preference = null;

    /**
     * @param iterable<FilterDefaultsProviderInterface> $providers
     */
    public function __construct(
        #[AutowireIterator(FilterDefaultsProviderInterface::class)]
        private readonly iterable $providers,
        private readonly Security $security,
        private readonly UserPreferenceRepository $userPreferenceRepository,
        private readonly CurrentSportProvider $currentSportProvider,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @return array<string, int|string>
     */
    public function resolve(string $route): array
    {
        $preference = $this->getPreference();
        $currentSport = $this->currentSportProvider->getSport();

        $defaults = [];
        foreach ($this->providers as $provider) {
            $defaults = [...$defaults, ...$provider->getDefaults($route, $preference, $currentSport)];
        }

        return $defaults;
    }

    /**
     * @param array<string, mixed> $parameters route parameters (e.g. an id), like in path()
     */
    public function generatePath(string $route, array $parameters = []): string
    {
        return $this->urlGenerator->generate($route, [...$parameters, ...$this->resolve($route)]);
    }

    public function reset(): void
    {
        $this->preference = null;
    }

    private function getPreference(): ?UserPreference
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return null;
        }

        if ($this->preference?->getUser() !== $user) {
            $this->preference = $this->userPreferenceRepository->findOrCreateForUser($user);
        }

        return $this->preference;
    }
}
