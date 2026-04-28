<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Person;
use App\Entity\Season;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Exception\CustomBadRequestException;
use App\Repository\TeamMemberRepository;
use App\Service\TeamMemberService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TeamMemberServiceTest extends TestCase
{
    private TeamMemberRepository&MockObject $teamMemberRepository;
    private EntityManagerInterface&MockObject $em;
    private TeamMemberService $service;

    protected function setUp(): void
    {
        $this->teamMemberRepository = $this->createMock(TeamMemberRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->service = new TeamMemberService(
            $this->teamMemberRepository,
            $this->em,
        );
    }

    #[Test]
    #[TestDox('Persists new TeamMember when ID is null')]
    public function saveTeamMemberPersistsWhenIdIsNull(): void
    {
        $teamMember = $this->createTeamMember(id: null, isCurrentMember: false);

        $this->em
            ->expects($this->once())
            ->method('wrapInTransaction')
            ->willReturnCallback(fn(callable $callback) => $callback());

        $this->em
            ->expects($this->once())
            ->method('persist')
            ->with($teamMember);

        $this->service->saveTeamMember($teamMember);
    }

    #[Test]
    #[TestDox('Does not persist when TeamMember already has an ID')]
    public function saveTeamMemberDoesNotPersistWhenIdIsSet(): void
    {
        $teamMember = $this->createTeamMember(id: 5, isCurrentMember: false);

        $this->em
            ->expects($this->once())
            ->method('wrapInTransaction')
            ->willReturnCallback(fn(callable $callback) => $callback());

        $this->em
            ->expects($this->never())
            ->method('persist');

        $this->service->saveTeamMember($teamMember);
    }

    #[Test]
    #[TestDox('Skips current member handling when isCurrentMember is false')]
    public function saveTeamMemberSkipsHandlingWhenNotCurrentMember(): void
    {
        $teamMember = $this->createTeamMember(id: null, isCurrentMember: false);

        $this->em
            ->method('wrapInTransaction')
            ->willReturnCallback(fn(callable $callback) => $callback());

        $this->teamMemberRepository
            ->expects($this->never())
            ->method('findOneBy');

        $this->service->saveTeamMember($teamMember);
    }

    #[Test]
    #[TestDox('Sets current team on person when no current member exists')]
    public function saveTeamMemberSetsCurrentTeamWhenNoCurrentMemberExists(): void
    {
        $team = $this->createMock(Team::class);
        $person = $this->createMock(Person::class);
        $person->expects($this->once())->method('setCurrentTeam')->with($team);

        $teamMember = $this->createTeamMember(id: null, isCurrentMember: true, person: $person, team: $team);

        $this->teamMemberRepository
            ->method('findOneBy')
            ->willReturn(null);

        $this->em
            ->method('wrapInTransaction')
            ->willReturnCallback(fn(callable $callback) => $callback());

        $this->service->saveTeamMember($teamMember);
    }

    #[Test]
    #[TestDox('Sets current team when existing current member is the same object')]
    public function saveTeamMemberSetsCurrentTeamWhenCurrentMemberIsSameObject(): void
    {
        $team = $this->createMock(Team::class);
        $person = $this->createMock(Person::class);
        $person->expects($this->once())->method('setCurrentTeam')->with($team);

        $teamMember = $this->createTeamMember(id: 1, isCurrentMember: true, person: $person, team: $team);

        $this->teamMemberRepository
            ->method('findOneBy')
            ->willReturn($teamMember);

        $this->em
            ->method('wrapInTransaction')
            ->willReturnCallback(fn(callable $callback) => $callback());

        $this->service->saveTeamMember($teamMember);
    }

    #[Test]
    #[TestDox('Throws CustomBadRequestException when current member exists for same team and season')]
    public function saveTeamMemberThrowsWhenCurrentMemberExistsForSameTeamAndSeason(): void
    {
        $team = $this->createMock(Team::class);
        $season = $this->createMock(Season::class);
        $person = $this->createMock(Person::class);

        $existingMember = $this->createTeamMember(
            id: 99,
            isCurrentMember: true,
            person: $person,
            team: $team,
            season: $season
        );
        $newMember = $this->createTeamMember(
            id: null,
            isCurrentMember: true,
            person: $person,
            team: $team,
            season: $season
        );

        $this->teamMemberRepository
            ->method('findOneBy')
            ->willReturn($existingMember);

        $this->em
            ->method('wrapInTransaction')
            ->willReturnCallback(fn(callable $callback) => $callback());

        $this->expectException(CustomBadRequestException::class);

        $this->service->saveTeamMember($newMember);
    }

    #[Test]
    #[TestDox('Deactivates old current member and sets new one when team differs')]
    public function saveTeamMemberDeactivatesOldCurrentMemberAndSetsNewOne(): void
    {
        $oldTeam = $this->createMock(Team::class);
        $newTeam = $this->createMock(Team::class);
        $person = $this->createMock(Person::class);
        $person->expects($this->once())->method('setCurrentTeam')->with($newTeam);

        $existingMember = $this->createMock(TeamMember::class);
        $existingMember->method('getTeam')->willReturn($oldTeam);
        $existingMember->method('getStartSeason')->willReturn(null);
        $existingMember->expects($this->once())->method('setIsCurrentMember')->with(false);

        $newMember = $this->createTeamMember(id: null, isCurrentMember: true, person: $person, team: $newTeam);

        $this->teamMemberRepository
            ->method('findOneBy')
            ->willReturn($existingMember);

        $this->em
            ->method('wrapInTransaction')
            ->willReturnCallback(fn(callable $callback) => $callback());

        $this->service->saveTeamMember($newMember);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createTeamMember(
        ?int $id,
        bool $isCurrentMember,
        ?Person $person = null,
        ?Team $team = null,
        ?Season $season = null,
    ): TeamMember&MockObject {
        $teamMember = $this->createMock(TeamMember::class);
        $teamMember->method('getId')->willReturn($id);
        $teamMember->method('isCurrentMember')->willReturn($isCurrentMember);
        $teamMember->method('getPerson')->willReturn($person ?? $this->createMock(Person::class));
        $teamMember->method('getTeam')->willReturn($team ?? $this->createMock(Team::class));
        $teamMember->method('getStartSeason')->willReturn($season);

        return $teamMember;
    }
}
