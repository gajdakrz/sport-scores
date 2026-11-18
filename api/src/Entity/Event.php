<?php

declare(strict_types=1);

namespace App\Entity;

use App\Helper\StringNormalizer;
use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\UniqueConstraint(
    name: 'uniq_event_competition_name',
    columns: ['competition_id', 'name'],
    options: ['where' => self::ACTIVE_ONLY]
)]
#[UniqueEntity(
    fields: ['competition', 'name'],
    message: 'An event with this name already exists in this competition.',
    repositoryMethod: 'findActiveBy',
    errorPath: 'name',
    groups: [self::UNIQUE_GROUP],
)]
#[Assert\GroupSequence(['Event', self::UNIQUE_GROUP])]
class Event extends AbstractAuditableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, options: ['collation' => self::CASE_INSENSITIVE])]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Competition::class, inversedBy: 'events')]
    #[ORM\JoinColumn(
        name: 'competition_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Competition $competition = null;

    /**
     * @var Collection<int, Game>
     */
    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'event', orphanRemoval: true)]
    private Collection $games;

    #[ORM\Column(nullable: true)]
    private ?int $orderIndex = null;

    public function __construct()
    {
        $this->games = new ArrayCollection();
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

    public function getCompetition(): ?Competition
    {
        return $this->competition;
    }

    public function setCompetition(Competition $competition): static
    {
        $this->competition = $competition;

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
            $game->setEvent($this);
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

    public function getOrderIndex(): ?int
    {
        return $this->orderIndex;
    }

    public function setOrderIndex(?int $orderIndex): void
    {
        $this->orderIndex = $orderIndex;
    }
}
