<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Sport;
use App\Exception\CustomBadRequestException;
use App\Repository\SportRepository;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class CurrentSportProvider
{
    public function __construct(
        private RequestStack $requestStack,
        private SportRepository $sportRepository
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

    public function requireSport(): Sport
    {
        $sport = $this->getSport();
        if (!$sport) {
            throw new CustomBadRequestException([
                ['message' => 'Sport not selected', 'field' => '']
            ]);
        }

        return $sport;
    }
}
