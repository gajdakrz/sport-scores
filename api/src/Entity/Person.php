<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Gender;
use App\Repository\PersonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
        name: 'country_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Country $country = null;

    #[ORM\Column(enumType: Gender::class)]
    private Gender $gender;

    /**
     * @var Collection<int, TeamMember>
     */
    #[ORM\OneToMany(targetEntity: TeamMember::class, mappedBy: 'person', orphanRemoval: false)]
    private Collection $teamMembers;

    /**
     * @var Collection<int, GameResult>
     */
    #[ORM\OneToMany(targetEntity: GameResult::class, mappedBy: 'person', orphanRemoval: false)]
    private Collection $gameResults;

    #[ORM\ManyToOne(targetEntity: Sport::class, inversedBy: 'persons')]
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

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(Country $country): static
    {
        $this->country = $country;

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

    public function removeTeamMember(TeamMember $teamMember, User $user): static
    {
        if ($this->teamMembers->contains($teamMember)) {
            $teamMember->setIsActive(false);
            $teamMember->setModifiedAt($this->now);
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

    public function removeGameResult(GameResult $gameResult, User $user): static
    {
        if ($this->gameResults->contains($gameResult)) {
            $gameResult->setIsActive(false);
            $gameResult->setModifiedAt($this->now);
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
