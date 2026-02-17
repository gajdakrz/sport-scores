<?php

namespace App\Entity;

use App\Repository\MemberPositionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MemberPositionRepository::class)]
class MemberPosition extends AbstractAuditableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Sport::class, inversedBy: 'memberPositions')]
    #[ORM\JoinColumn(
        name: 'sport_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Sport $sport = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, TeamMember>
     */
    #[ORM\OneToMany(targetEntity: TeamMember::class, mappedBy: 'MemberPosition', orphanRemoval: false)]
    private Collection $teamMembers;

    public function __construct()
    {
        parent::__construct();
        $this->teamMembers = new ArrayCollection();
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

    public function getSport(): ?Sport
    {
        return $this->sport;
    }

    public function setSport(?Sport $sport): static
    {
        $this->sport = $sport;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, TeamMember>
     */
    public function getTeamMembers(): Collection
    {
        return $this->teamMembers;
    }

    public function addTeamMember(TeamMember $teamMember): static
    {
        if (!$this->teamMembers->contains($teamMember)) {
            $this->teamMembers->add($teamMember);
            $teamMember->setMemberPosition($this);
        }

        return $this;
    }

    public function removeTeamMember(TeamMember $teamMember): static
    {
        // jezeli dopuszczam w polu null to orphanRemoval: false i ponizej mogę ustawić null
        if ($this->teamMembers->removeElement($teamMember) && $teamMember->getMemberPosition() === $this) {
            $teamMember->setMemberPosition(null);
        }

        return $this;
    }

    public function deactivatePosition(TeamMember $teamMember, User $user): static
    {
        if ($this->teamMembers->contains($teamMember)) {
            $teamMember->setIsActive(false);
            $teamMember->setModifiedBy($user);
        }

        return $this;
    }
}
