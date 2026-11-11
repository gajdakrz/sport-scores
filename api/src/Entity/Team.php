<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Gender;
use App\Enum\GenderCategory;
use App\Enum\TeamType;
use App\Validator\TeamCompetitionCountryAllowed;
use App\Validator\TeamGenderConsistent;
use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[TeamGenderConsistent]
#[TeamCompetitionCountryAllowed]
class Team extends AbstractAuditableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Country::class, inversedBy: 'teams')]
    #[ORM\JoinColumn(
        name: 'country_id',
        referencedColumnName: 'id',
        nullable: true
    )]
    private ?Country $country = null;

    /**
     * Country whose national competitions the team plays in – may differ from the country of origin
     * (e.g. AS Ukraine Jastrzębie Barkom plays in Poland volleyball league).
     * Null = not applicable (e.g. a national team): the team plays only in international competitions.
     */
    #[ORM\ManyToOne(targetEntity: Country::class)]
    #[ORM\JoinColumn(
        name: 'competition_country_id',
        referencedColumnName: 'id',
        nullable: true
    )]
    private ?Country $competitionCountry = null;

    #[ORM\Column(enumType: TeamType::class)]
    private TeamType $teamType;

    #[ORM\Column(enumType: GenderCategory::class)]
    private GenderCategory $gender;

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

    /**
     * @var Collection<int, Person>
     */
    #[ORM\OneToMany(targetEntity: Person::class, mappedBy: 'currentTeam')]
    private Collection $persons;

    #[ORM\ManyToOne(targetEntity: Sport::class)]
    #[ORM\JoinColumn(
        name: 'sport_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Sport $sport = null;

    public function __construct()
    {
        $this->persons = new ArrayCollection();
        $this->gameResults = new ArrayCollection();
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

    public function getCompetitionCountry(): ?Country
    {
        return $this->competitionCountry;
    }

    public function setCompetitionCountry(?Country $competitionCountry): static
    {
        $this->competitionCountry = $competitionCountry;

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
     * National teams play only international competitions, so they have no competition country.
     */
    public function isNational(): bool
    {
        // teamType is unset on a new team whose form field was empty or invalid
        return isset($this->teamType) && $this->teamType === TeamType::NATIONAL;
    }

    public function getGender(): GenderCategory
    {
        return $this->gender;
    }

    public function setGender(GenderCategory $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Membership rule: a MIXED team accepts persons of every gender.
     */
    public function acceptsGender(Gender $gender): bool
    {
        return $this->gender->accepts($gender);
    }

    /**
     * Competition rule: exact match – a MIXED team plays only in a MIXED competition.
     */
    public function canPlayIn(Competition $competition): bool
    {
        return $this->gender === $competition->getGender();
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

    /**
     * @return Collection<int, Person>
     */
    public function getPersons(): Collection
    {
        return $this->persons;
    }

    public function addPersons(Person $person): static
    {
        if (!$this->persons->contains($person)) {
            $this->persons->add($person);
            $person->setCurrentTeam($this);
        }

        return $this;
    }

    public function removePerson(Person $person): static
    {
        $this->persons->removeElement($person);

        return $this;
    }

    public function deactivatePerson(Person $person, User $user): static
    {
        if ($this->persons->contains($person)) {
            $person->setIsActive(false);
            $person->setModifiedBy($user);
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
