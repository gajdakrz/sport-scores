<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Sport;
use App\Exception\CustomBadRequestException;
use App\Repository\SportRepository;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class CurrentSportProvider
{
    private const string SESSION_KEY = 'current_sport_id';

    public function __construct(
        private RequestStack $requestStack,
        private SportRepository $sportRepository
    ) {
    }

    public function getSport(): ?Sport
    {
        $session = $this->requestStack->getSession();
        $sportId = $session->get(self::SESSION_KEY);

        return $sportId
            ? $this->sportRepository->find($sportId)
            : null;
    }

    public function setSport(Sport $sport): void
    {
        $this->requestStack->getSession()->set(self::SESSION_KEY, $sport->getId());
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
