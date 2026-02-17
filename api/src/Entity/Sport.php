<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SportRepository::class)]
class Sport extends AbstractAuditableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, Competition>
     */
    #[ORM\OneToMany(targetEntity: Competition::class, mappedBy: 'sport', orphanRemoval: true)]
    private Collection $competitions;

    /**
     * @var Collection<int, MemberPosition>
     */
    #[ORM\OneToMany(targetEntity: MemberPosition::class, mappedBy: 'sport', orphanRemoval: true)]
    private Collection $memberPositions;

    public function __construct()
    {
        parent::__construct();
        $this->competitions = new ArrayCollection();
        $this->memberPositions = new ArrayCollection();
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
     * @return Collection<int, Competition>
     */
    public function getCompetitions(): Collection
    {
        return $this->competitions;
    }

    public function addCompetition(Competition $competition): static
    {
        if (!$this->competitions->contains($competition)) {
            $this->competitions->add($competition);
            $competition->setSport($this);
        }

        return $this;
    }

    public function removeCompetition(Competition $competition): static
    {
        $this->competitions->removeElement($competition);

        return $this;
    }

    public function deactivateCompetition(Competition $competition, User $user): static
    {
        if ($this->competitions->contains($competition)) {
            $competition->setIsActive(false);
            $competition->setModifiedBy($user);
        }

        return $this;
    }

    /**
     * @return Collection<int, MemberPosition>
     */
    public function getMemberPositions(): Collection
    {
        return $this->memberPositions;
    }

    public function addMemberPosition(MemberPosition $memberPosition): static
    {
        if (!$this->memberPositions->contains($memberPosition)) {
            $this->memberPositions->add($memberPosition);
            $memberPosition->setSport($this);
        }

        return $this;
    }

    public function removeMemberPosition(MemberPosition $memberPosition): static
    {
        $this->memberPositions->removeElement($memberPosition);

        return $this;
    }

    public function deactivatePosition(MemberPosition $memberPosition, User $user): static
    {
        if ($this->memberPositions->contains($memberPosition)) {
            $memberPosition->setIsActive(false);
            $memberPosition->setModifiedBy($user);
        }

        return $this;
    }
}
