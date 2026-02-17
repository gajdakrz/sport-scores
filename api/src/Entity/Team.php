<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TeamType;
use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
class Team extends AbstractAuditableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Country::class, inversedBy: 'persons')]
    #[ORM\JoinColumn(
        name: 'country_id',
        referencedColumnName: 'id',
        nullable: true
    )]
    private ?Country $country = null;

    #[ORM\Column(enumType: TeamType::class)]
    private TeamType $teamType;

    /**
     * @var Collection<int, TeamMember>
     */
    #[ORM\OneToMany(targetEntity: TeamMember::class, mappedBy: 'team', orphanRemoval: true)]
    private Collection $teamMembers;

    /**
     * @var Collection<int, GameResult>
     */
    #[ORM\OneToMany(targetEntity: GameResult::class, mappedBy: 'team', orphanRemoval: true)]
    private Collection $gameResults;

    #[ORM\ManyToOne(targetEntity: Sport::class, inversedBy: 'teams')]
    #[ORM\JoinColumn(
        name: 'sport_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Sport $sport = null;

    public function __construct()
    {
        parent::__construct();
        $this->teamMembers = new ArrayCollection();
        $this->gameResults = new ArrayCollection();
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

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getTeamType(): TeamType
    {
        return $this->teamType;
    }

    public function setTeamType(TeamType $teamType): static
    {
        $this->teamType = $teamType;

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
            $teamMember->setTeam($this);
        }

        return $this;
    }

    public function removeTeamMember(TeamMember $teamMember): static
    {
        $this->teamMembers->removeElement($teamMember);

        return $this;
    }

    public function deactivateTeamMember(TeamMember $teamMember, User $user): static
    {
        if ($this->teamMembers->contains($teamMember)) {
            $teamMember->setIsActive(false);
            $teamMember->setModifiedBy($user);
        }

        return $this;
    }

    /**
     * @return Collection<int, GameResult>
     */
    public function getGameResults(): Collection
    {
        return $this->gameResults;
    }

    public function addGameResult(GameResult $gameResult): static
    {
        if (!$this->gameResults->contains($gameResult)) {
            $this->gameResults->add($gameResult);
            $gameResult->setTeam($this);
        }

        return $this;
    }

    public function removeGameResult(GameResult $gameResult): static
    {
        $this->gameResults->removeElement($gameResult);

        return $this;
    }

    public function deactivateGameResult(GameResult $gameResult, User $user): static
    {
        if ($this->gameResults->contains($gameResult)) {
            $gameResult->setIsActive(false);
            $gameResult->setModifiedBy($user);
        }

        return $this;
    }

    public function getSport(): ?Sport
    {
        return $this->sport;
    }

    public function setSport(Sport $sport): static
    {
        $this->sport = $sport;

        return $this;
    }
}
