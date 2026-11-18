<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Controller\BaseController;
use App\Entity\Sport;
use App\Entity\User;
use App\Repository\SportRepository;
use App\Repository\UserPreferenceRepository;
use App\Service\CurrentSportProvider;
use App\Service\Preference\PreferredFilterResolver;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends BaseController
{
    public function __construct(
        private readonly CurrentSportProvider $currentSportProvider,
        private readonly UserPreferenceRepository $userPreferenceRepository,
    ) {
    }

    /**
     * Sport selection – only when no sport is known yet. With a sport in the session (e.g. picked in another tab)
     * or a favourite sport, the user goes straight to Games, like after the login.
     */
    #[Route('/', name: 'app_home')]
    public function index(SportRepository $sportRepository, PreferredFilterResolver $preferredFilterResolver): Response
    {
        if ($this->resolveSport() !== null) {
            return $this->redirect($preferredFilterResolver->generatePath('game_index'));
        }

        return $this->render('home/index.html.twig', [
            'sports' => $sportRepository->findActiveSortedBy('name', 'ASC')
        ]);
    }

    /**
     * The active sport from the session, otherwise the user's active favourite sport (then set as the current one).
     */
    private function resolveSport(): ?Sport
    {
        $sport = $this->currentSportProvider->getSport();
        if ($sport?->isActive()) {
            return $sport;
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return null;
        }

        $favourite = $this->userPreferenceRepository->findOrCreateForUser($user)->getActiveFavouriteSport();
        if ($favourite !== null) {
            $this->currentSportProvider->setSport($favourite);
        }

        return $favourite;
    }
}
