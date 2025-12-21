<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Sport;
use App\Repository\SportRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CurrentSportProvider
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly SportRepository $sportRepository
    ) {
    }

    public function getSport(): ?Sport
    {
        $session = $this->requestStack->getSession();
        $sportId = $session->get('current_sport_id');

        return $sportId
            ? $this->sportRepository->find($sportId)
            : null;
    }

    public function getSportId(): ?int
    {
        return $this->getSport()?->getId();
    }

    public function getSportName(): ?string
    {
        return $this->getSport()?->getName();
    }
}
