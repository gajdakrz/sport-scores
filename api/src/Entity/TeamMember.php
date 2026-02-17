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

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['default' => 'CURRENT_DATE'])]
    private DateTimeImmutable $startDate;


    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $endDate = null;

    #[ORM\ManyToOne(inversedBy: 'teamCompetitors')]
    #[ORM\JoinColumn(
        name: 'team_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Team $team = null;

    #[ORM\ManyToOne(inversedBy: 'teamCompetitors')]
    #[ORM\JoinColumn(
        name: 'person_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Person $person = null;

    #[ORM\ManyToOne(targetEntity: Season::class, inversedBy: 'games')]
    #[ORM\JoinColumn(
        name: 'season_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Season $season = null;

    #[ORM\ManyToOne(inversedBy: 'teamMembers')]
    #[ORM\JoinColumn(nullable: true)]
    private ?MemberPosition $memberPosition = null;

    public function __construct()
    {
        parent::__construct();
        $this->startDate = $this->createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

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

    public function getSeason(): ?Season
    {
        return $this->season;
    }

    public function setSeason(?Season $season): static
    {
        $this->season = $season;

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
}
