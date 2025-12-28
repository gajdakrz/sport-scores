<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SeasonRepository;
use App\Validator\SeasonYearRange;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SeasonRepository::class)]
#[SeasonYearRange]
class Season extends AbstractAuditableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $startYear = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $endYear = null;

    /**
     * @var Collection<int, Game>
     */
    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'season', orphanRemoval: false)]
    private Collection $games;

    /**
     * @var Collection<int, TeamMember>
     */
    #[ORM\OneToMany(targetEntity: TeamMember::class, mappedBy: 'season', orphanRemoval: false)]
    private Collection $teamMembers;

    public function __construct()
    {
        parent::__construct();
        $this->games = new ArrayCollection();
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

    public function getStartYear(): ?int
    {
        return $this->startYear;
    }

    public function setStartYear(int $startYear): static
    {
        $this->startYear = $startYear;

        return $this;
    }

    public function getEndYear(): ?int
    {
        return $this->endYear;
    }

    public function setEndYear(int $endYear): static
    {
        $this->endYear = $endYear;

        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getGames(): Collection
    {
        return $this->games;
    }

    public function addGame(Game $game): static
    {
        if (!$this->games->contains($game)) {
            $this->games->add($game);
            $game->setSeason($this);
        }

        return $this;
    }

    public function removeGame(Game $game, User $user): static
    {
        if ($this->games->contains($game)) {
            $game->setIsActive(false);
            $game->setModifiedAt($this->now);
            $game->setModifiedBy($user);
        }

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
            $teamMember->setSeason($this);
        }

        return $this;
    }

    public function removeTeamMember(TeamMember $teamMember, User $user): static
    {
        if ($this->teamMembers->contains($teamMember)) {
            $teamMember->setIsActive(false);
            $teamMember->setModifiedAt($this->now);
            $teamMember->setModifiedBy($user);
        }

        return $this;
    }

    public function getMergedStartEndYear(): string
    {
        if ($this->startYear === $this->endYear) {
            return (string) $this->startYear;
        }

        return $this->startYear . ' - ' . $this->endYear;
    }
}
