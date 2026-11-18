<?php

declare(strict_types=1);

namespace App\Entity;

use App\Helper\StringNormalizer;
use App\Repository\MemberPositionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: MemberPositionRepository::class)]
#[ORM\UniqueConstraint(
    name: 'uniq_member_position_sport_name',
    columns: ['sport_id', 'name'],
    options: ['where' => self::ACTIVE_ONLY]
)]
#[UniqueEntity(
    fields: ['sport', 'name'],
    message: 'This position already exists in this sport.',
    repositoryMethod: 'findActiveBy',
    errorPath: 'name',
    groups: [self::UNIQUE_GROUP],
)]
#[Assert\GroupSequence(['MemberPosition', self::UNIQUE_GROUP])]
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

    #[ORM\Column(length: 255, options: ['collation' => self::CASE_INSENSITIVE])]
    private ?string $name = null;

    /**
     * @var Collection<int, TeamMember>
     */
    #[ORM\OneToMany(targetEntity: TeamMember::class, mappedBy: 'memberPosition', orphanRemoval: false)]
    private Collection $teamMembers;

    public function __construct()
    {
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
        $this->name = StringNormalizer::normalizeWhitespace($name);

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
        // the field is nullable, so orphanRemoval is false and the position can be set to null below
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
