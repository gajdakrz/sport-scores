<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EventRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event extends AbstractAuditableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
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
    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'event', orphanRemoval: false)]
    private Collection $games;

    public function __construct()
    {
        parent::__construct();
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
        $this->name = $name;

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
        if ($this->games->contains($game) && $game->isActive()) {
            $game->setIsActive(false);
            $game->setModifiedAt($this->now);
        }

        return $this;
    }

    public function deactivateGame(Game $game, User $user): static
    {
        if ($this->games->contains($game)) {
            $game->setIsActive(false);
            $game->setModifiedAt($this->now);
            $game->setModifiedBy($user);
        }

        return $this;
    }
}
