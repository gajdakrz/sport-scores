<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TeamMemberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamMemberRepository::class)]
class TeamMember extends AbstractAuditableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private string $startDate;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $endDate = null;

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

    public function __construct()
    {
        parent::__construct();
        $this->startDate = $this->now->format(self::DATE_FORMAT);
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

    public function getStartDate(): ?string
    {
        return $this->startDate;
    }

    public function setStartDate(string $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?string
    {
        return $this->endDate;
    }

    public function setEndDate(?string $endDate): static
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
}
