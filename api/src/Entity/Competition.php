<?php

declare(strict_types=1);

namespace App\Entity;

use App\Helper\StringNormalizer;
use App\Enum\GenderCategory;
use App\Repository\CompetitionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: CompetitionRepository::class)]
#[ORM\UniqueConstraint(
    name: 'uniq_competition_key',
    columns: ['name', 'sport_id', 'gender', 'country_id'],
    options: ['where' => self::ACTIVE_ONLY]
)]
#[ORM\UniqueConstraint(
    name: 'uniq_competition_key_international',
    columns: ['name', 'sport_id', 'gender'],
    options: ['where' => '((country_id IS NULL) AND (is_active = true))']
)]
#[UniqueEntity(
    fields: ['name', 'sport', 'gender', 'country'],
    message: 'A competition with this name, sport, gender and country already exists.',
    repositoryMethod: 'findActiveBy',
    errorPath: 'name',
    ignoreNull: false,
    groups: [self::UNIQUE_GROUP],
)]
#[Assert\GroupSequence(['Competition', self::UNIQUE_GROUP])]
class Competition extends AbstractAuditableEntity implements SportScopedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, options: ['collation' => self::CASE_INSENSITIVE])]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Sport::class, inversedBy: 'competitions')]
    #[ORM\JoinColumn(
        name: 'sport_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Sport $sport = null;

    #[ORM\Column(enumType: GenderCategory::class)]
    private GenderCategory $gender;

    /**
     * Null means an international competition (teams from every country may play).
     */
    #[ORM\ManyToOne(targetEntity: Country::class)]
    #[ORM\JoinColumn(
        name: 'country_id',
        referencedColumnName: 'id',
        nullable: true
    )]
    private ?Country $country = null;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'competition', orphanRemoval: true)]
    private Collection $events;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => 0])]
    private bool $isBracket = false;

    public function __construct()
    {
        $this->events = new ArrayCollection();
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
        $this->name = StringNormalizer::normalizeWhitespace($name);

        return $this;
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

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function isInternational(): bool
    {
        return $this->country === null;
    }

    /**
     * Country rule: an international competition admits every team,
     * a national one only teams whose competition country is its country
     * (a team without a competition country, e.g. a national team, is rejected).
     */
    public function admitsTeam(Team $team): bool
    {
        return $this->isInternational() || $this->country === $team->getCompetitionCountry();
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setCompetition($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        $this->events->removeElement($event);

        return $this;
    }

    public function deactivateEvent(Event $event, User $user): static
    {
        if ($this->events->contains($event)) {
            $event->setIsActive(false);
            $event->setModifiedBy($user);
        }

        return $this;
    }

    public function isBracket(): bool
    {
        return $this->isBracket;
    }

    public function setIsBracket(bool $isBracket): static
    {
        $this->isBracket = $isBracket;

        return $this;
    }
}
