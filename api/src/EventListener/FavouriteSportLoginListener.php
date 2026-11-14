<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Repository\UserPreferenceRepository;
use App\Service\CurrentSportProvider;
use App\Service\Preference\PreferredFilterResolver;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * After a form login, a user with a favourite sport doesn't have to pick a sport: it is set as the current one,
 * and a login that would land on the home page (sport selection) goes straight to Games instead.
 */
#[AsEventListener]
final readonly class FavouriteSportLoginListener
{
    private const string FIREWALL = 'main';

    public function __construct(
        private UserPreferenceRepository $userPreferenceRepository,
        private CurrentSportProvider $currentSportProvider,
        private PreferredFilterResolver $preferredFilterResolver,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if ($event->getFirewallName() !== self::FIREWALL || !$user instanceof User) {
            return;
        }

        $sport = $this->userPreferenceRepository->findOrCreateForUser($user)->getActiveFavouriteSport();
        if ($sport === null) {
            return;
        }

        $this->currentSportProvider->setSport($sport);

        if ($this->redirectsToHome($event)) {
            $event->setResponse(new RedirectResponse($this->preferredFilterResolver->generatePath('game_index')));
        }
    }

    /**
     * True when the login has no other target (e.g. a page the user was sent away from).
     */
    private function redirectsToHome(LoginSuccessEvent $event): bool
    {
        $response = $event->getResponse();
        if (!$response instanceof RedirectResponse) {
            return false;
        }

        $target = parse_url($response->getTargetUrl());

        return ($target['path'] ?? '/') === $this->urlGenerator->generate('app_home')
            && !isset($target['query']);
    }
}
