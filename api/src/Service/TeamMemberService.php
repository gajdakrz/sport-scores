<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\TeamMember;
use App\Exception\CustomBadRequestException;
use App\Repository\TeamMemberRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

class TeamMemberService
{
    public function __construct(
        private readonly TeamMemberRepository $teamMemberRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @throws RuntimeException jeśli current member już istnieje dla tego teamu
     */
    public function createTeamMember(TeamMember $teamMember): void
    {
        $this->em->wrapInTransaction(function () use ($teamMember): void {
            $this->handleCurrentMember($teamMember);
            $this->em->persist($teamMember);
            // flush jest wykonywany automatycznie przez wrapInTransaction
        });
    }

    private function handleCurrentMember(TeamMember $teamMember): void
    {
        if ($teamMember->isCurrentMember() === false) {
            return;
        }

        $currentTeamMember = $this->getCurrentMemberForPerson($teamMember);

        if ($currentTeamMember === null) {
            $this->setCurrentTeamInPerson($teamMember);

            return;
        }

        if (
            $currentTeamMember->getId() !== $teamMember->getId()
            && $currentTeamMember->getTeam() === $teamMember->getTeam()
            && $currentTeamMember->getStartSeason() === $teamMember->getStartSeason()
        ) {
            $errors[] = [
                'message' => 'Current member already exists for this team in this start season',
                'field' => '',
            ];
            throw new CustomBadRequestException($errors);
        }

        if ($currentTeamMember->getId() !== $teamMember->getId()) {
            $this->deactivateOldTeamMember($currentTeamMember);
        }
        $this->setCurrentTeamInPerson($teamMember);
    }

    private function getCurrentMemberForPerson(TeamMember $teamMember): ?TeamMember
    {
        return $this->teamMemberRepository->findOneBy([
            'person' => $teamMember->getPerson(),
            'isCurrentMember' => true,
            'isActive' => true,
        ]);
    }

    private function deactivateOldTeamMember(TeamMember $currentTeamMember): void
    {
        $currentTeamMember->setIsCurrentMember(false);
    }

    private function setCurrentTeamInPerson(TeamMember $teamMember): void
    {
        $teamMember->getPerson()?->setCurrentTeam($teamMember->getTeam());
    }
}
