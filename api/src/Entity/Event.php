<?php

namespace App\Entity;

use App\Repository\EventRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    private const string DATE_FORMAT = 'Y-m-d';
    private DateTimeImmutable $dateTimeNow;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'boolean', options: ['default' => 1])]
    private bool $isActive = true;

    #[ORM\ManyToOne(targetEntity: Competition::class, inversedBy: 'events')]
    #[ORM\JoinColumn(
        name: 'competition_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private Competition $competition;

    #[ORM\Column(length: 10)]
    private string $startDate;

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

    public function __construct()
    {
        $this->dateTimeNow = new DateTimeImmutable();
        $this->setCreatedAt($this->dateTimeNow);
        $this->setModifiedAt($this->dateTimeNow);
        $this->startDate = $this->dateTimeNow->format(self::DATE_FORMAT);
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

    public function getCompetition(): Competition
    {
        return $this->competition;
    }

    public function setCompetition(Competition $competition): static
    {
        $this->competition = $competition;

        return $this;
    }

    public function getStartDate(): string
    {
        return $this->startDate;
    }

    public function setStartDate(string $startDate): static
    {
        $this->startDate = $startDate;

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
}
