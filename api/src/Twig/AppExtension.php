<?php

declare(strict_types=1);

namespace App\Twig;

use App\Repository\SportRepository;
use App\Service\CurrentSportProvider;
use App\Service\Preference\PreferredFilterResolver;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly SportRepository $sportRepository,
        private readonly CurrentSportProvider $currentSportProvider,
        private readonly PreferredFilterResolver $preferredFilterResolver,
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'sports' => $this->sportRepository->findAll(),
            'currentSport' => $this->currentSportProvider,
        ];
    }

    public function getFunctions(): array
    {
        return [
            // path() of a list page with the filters preset from the user's preferences
            new TwigFunction('preferred_path', $this->preferredFilterResolver->generatePath(...)),
        ];
    }
}
