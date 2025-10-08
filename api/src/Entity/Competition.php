<?php

namespace App\Entity;

use App\Enum\Gender;
use App\Repository\CompetitionRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompetitionRepository::class)]
class Competition
{
    private DateTimeImmutable $dateTimeNow;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'boolean', options: ['default' => 1])]
    private bool $isActive = true;

    #[ORM\ManyToOne(targetEntity: Sport::class, inversedBy: 'competitions')]
    #[ORM\JoinColumn(
        name: 'sport_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private Sport $sport;

    #[ORM\Column(enumType: Gender::class)]
    private Gender $gender;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(
        name: 'created_user_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private User $createdBy;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private DateTimeImmutable $modifiedAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(
        name: 'modified_user_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private User $modifiedBy;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'competition', orphanRemoval: true)]
    private Collection $events;

    public function __construct()
    {
        $this->dateTimeNow = new DateTimeImmutable();
        $this->setCreatedAt($this->dateTimeNow);
        $this->setModifiedAt($this->dateTimeNow);
        $this->events = new ArrayCollection();
    }

    public function getSport(): Sport
    {
        return $this->sport;
    }

    public function setSport(Sport $sport): static
    {
        $this->sport = $sport;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
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

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $user): static
    {
        $this->createdBy = $user;
        return $this;
    }

    public function getModifiedAt(): DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(DateTimeImmutable $modifiedAt): static
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    public function getModifiedBy(): User
    {
        return $this->modifiedBy;
    }

    public function setModifiedBy(User $user): static
    {
        $this->modifiedBy = $user;
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
        $this->events->removeElement($event);

        return $this;
    }
}
