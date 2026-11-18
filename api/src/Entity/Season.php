<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SeasonRepository;
use App\Validator\SeasonYearRange;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: SeasonRepository::class)]
#[ORM\UniqueConstraint(
    name: 'uniq_season_years',
    columns: ['start_year', 'end_year'],
    options: ['where' => self::ACTIVE_ONLY]
)]
#[UniqueEntity(
    fields: ['startYear', 'endYear'],
    message: 'A season with these years already exists.',
    repositoryMethod: 'findActiveBy',
    errorPath: 'startYear',
    groups: [self::UNIQUE_GROUP],
)]
#[Assert\GroupSequence(['Season', self::UNIQUE_GROUP])]
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
    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'season', orphanRemoval: true)]
    private Collection $games;

    /**
     * @var Collection<int, TeamMember>
     */
    #[ORM\OneToMany(targetEntity: TeamMember::class, mappedBy: 'startSeason')]
    private Collection $teamMembers;

    public function __construct()
    {
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

    public function removeGame(Game $game): static
    {
        $this->games->removeElement($game);

        return $this;
    }

    public function deactivateGame(Game $game, User $user): static
    {
        if ($this->games->contains($game)) {
            $game->setIsActive(false);
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
            $teamMember->setStartSeason($this);
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

    public function getMergedStartEndYear(): string
    {
        if ($this->startYear === $this->endYear) {
            return (string) $this->startYear;
        }

        return $this->startYear . ' - ' . $this->endYear;
    }
}
