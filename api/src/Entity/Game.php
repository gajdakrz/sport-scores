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
    public const MIN_RESULTS = 2;
    public const MIN_RESULTS_MESSAGE = 'A game must have at least two results.';

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
        orphanRemoval: true
    )]
    #[Assert\Valid]
    private Collection $gameResults;

    public function __construct()
    {
        $this->gameResults = new ArrayCollection();
        $this->date = new DateTimeImmutable();
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

    public function setSeason(?Season $season): static
    {
        $this->season = $season;

        return $this;
    }

    /**
     * @return Collection<int, GameResult>
     */
    #[Assert\Count(min: self::MIN_RESULTS, minMessage: self::MIN_RESULTS_MESSAGE)]
    public function getGameResults(): Collection
    {
        return $this->gameResults->filter(
            fn(GameResult $gameResult) => $gameResult->isActive()
        );
    }

    /**
     * Whether one active result can be taken away (deleted or moved to another game)
     * without dropping below MIN_RESULTS.
     */
    public function canLoseResult(): bool
    {
        return $this->getGameResults()->count() > self::MIN_RESULTS;
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
}
