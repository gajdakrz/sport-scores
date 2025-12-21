<?php

declare(strict_types=1);

namespace App\Twig;

use App\Repository\SportRepository;
use App\Service\CurrentSportProvider;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly SportRepository $sportRepository,
        private readonly CurrentSportProvider $currentSportProvider,
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'sports' => $this->sportRepository->findAll(),
            'currentSport' => $this->currentSportProvider,
        ];
    }
}
