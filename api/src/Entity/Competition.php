<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Gender;
use App\Repository\CompetitionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompetitionRepository::class)]
class Competition extends AbstractAuditableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Sport::class, inversedBy: 'competitions')]
    #[ORM\JoinColumn(
        name: 'sport_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Sport $sport = null;

    #[ORM\Column(enumType: Gender::class)]
    private Gender $gender;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'competition', orphanRemoval: false)]
    private Collection $events;

    public function __construct()
    {
        parent::__construct();
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
        $this->name = $name;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

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
        if ($this->events->contains($event) && $event->isActive()) {
            $event->setIsActive(false);
            $event->setModifiedAt($this->now);
        }

        return $this;
    }

    public function deactivateEvent(Event $event, User $user): static
    {
        if ($this->events->contains($event)) {
            $event->setIsActive(false);
            $event->setModifiedAt($this->now);
            $event->setModifiedBy($user);
        }

        return $this;
    }
}
