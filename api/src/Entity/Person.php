<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Gender;
use App\Repository\PersonRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonRepository::class)]
class Person extends AbstractAuditableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\ManyToOne(targetEntity: Country::class, inversedBy: 'persons')]
    #[ORM\JoinColumn(
        name: 'origin_country_id',
        referencedColumnName: 'id',
        nullable: true
    )]
    private ?Country $originCountry = null;

    #[ORM\Column(enumType: Gender::class)]
    private Gender $gender;

    /**
     * @var Collection<int, TeamMember>
     */
    #[ORM\OneToMany(targetEntity: TeamMember::class, mappedBy: 'person', orphanRemoval: true)]
    private Collection $teamMembers;

    /**
     * @var Collection<int, GameResult>
     */
    #[ORM\OneToMany(targetEntity: GameResult::class, mappedBy: 'person', orphanRemoval: true)]
    private Collection $gameResults;

    #[ORM\ManyToOne(targetEntity: Sport::class, inversedBy: 'persons')]
    #[ORM\JoinColumn(
        name: 'sport_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Sport $sport = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $birthDate = null;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'persons')]
    #[ORM\JoinColumn(
        name: 'current_team_id',
        referencedColumnName: 'id',
        nullable: true
    )]
    private ?Team $currentTeam = null;

    public function __construct()
    {
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

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getOriginCountry(): ?Country
    {
        return $this->originCountry;
    }

    public function setOriginCountry(Country $originCountry): static
    {
        $this->originCountry = $originCountry;

        return $this;
    }

    public function getGender(): Gender
    {
        return $this->gender;
    }

    public function setGender(Gender $gender): static
    {
        $this->gender = $gender;

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
            $teamMember->setPerson($this);
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
            $gameResult->setPerson($this);
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

    public function getBirthDate(): ?DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(?DateTimeImmutable $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getCurrentTeam(): ?Team
    {
        return $this->currentTeam;
    }

    public function setCurrentTeam(?Team $currentTeam): static
    {
        $this->currentTeam = $currentTeam;

        return $this;
    }
}
