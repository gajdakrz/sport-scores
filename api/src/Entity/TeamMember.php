<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TeamMemberRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamMemberRepository::class)]
class TeamMember extends AbstractAuditableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'teamMembers')]
    #[ORM\JoinColumn(
        name: 'team_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Team $team = null;

    #[ORM\ManyToOne(inversedBy: 'teamMembers')]
    #[ORM\JoinColumn(
        name: 'person_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Person $person = null;

    #[ORM\ManyToOne(targetEntity: Season::class, inversedBy: 'teamMembers')]
    #[ORM\JoinColumn(
        name: 'start_season_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Season $startSeason = null;

    #[ORM\ManyToOne(inversedBy: 'teamMembers')]
    #[ORM\JoinColumn(nullable: true)]
    private ?MemberPosition $memberPosition = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => 1])]
    protected bool $isCurrentMember = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;

        return $this;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setPerson(?Person $person): static
    {
        $this->person = $person;

        return $this;
    }

    public function getStartSeason(): ?Season
    {
        return $this->startSeason;
    }

    public function setStartSeason(?Season $startSeason): static
    {
        $this->startSeason = $startSeason;

        return $this;
    }

    public function getMemberPosition(): ?MemberPosition
    {
        return $this->memberPosition;
    }

    public function setMemberPosition(?MemberPosition $memberPosition): static
    {
        $this->memberPosition = $memberPosition;

        return $this;
    }

    public function isCurrentMember(): bool
    {
        return $this->isCurrentMember;
    }

    public function setIsCurrentMember(bool $isCurrentMember): static
    {
        $this->isCurrentMember = $isCurrentMember;

        return $this;
    }
}
