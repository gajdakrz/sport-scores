<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GameRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game extends AbstractAuditableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['default' => 'CURRENT_DATE'])]
    private DateTimeImmutable $date;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'games')]
    #[ORM\JoinColumn(
        name: 'event_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Event $event = null;

    #[ORM\ManyToOne(targetEntity: Season::class, inversedBy: 'games')]
    #[ORM\JoinColumn(
        name: 'season_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Season $season = null;

    /**
     * @var Collection<int, GameResult>
     */
    #[ORM\OneToMany(
        targetEntity: GameResult::class,
        mappedBy: 'game',
        cascade: ['persist', 'remove'],
        orphanRemoval: false
    )]
    #[Assert\Valid]
    private Collection $gameResults;

    public function __construct()
    {
        parent::__construct();
        $this->gameResults = new ArrayCollection();
        $this->date = $this->now;
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

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getSeason(): ?Season
    {
        return $this->season;
    }

    public function setSeason(Season $season): static
    {
        $this->season = $season;

        return $this;
    }

    /**
     * @return Collection<int, GameResult>
     */
    public function getGameResults(): Collection
    {
        return $this->gameResults->filter(
            fn(GameResult $gameResult) => $gameResult->isActive()
        );
    }

    /**
     * @return Collection<int, GameResult>
     */
    public function getAllGameResults(): Collection
    {
        return $this->gameResults;
    }

    public function addGameResult(GameResult $gameResult): static
    {
        if (!$this->gameResults->contains($gameResult)) {
            $this->gameResults->add($gameResult);
            $gameResult->setGame($this);
        }

        return $this;
    }

    public function removeGameResult(GameResult $gameResult): static
    {
        if ($this->gameResults->contains($gameResult)) {
            $gameResult->setIsActive(false);
            // NIE usuwamy z kolekcji: $this->gameResults->removeElement($gameResult);
        }

        return $this;
    }
}
