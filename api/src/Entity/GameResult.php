<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GameResultRepository;
use App\Validator\AtLeastOneScore;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameResultRepository::class)]
#[AtLeastOneScore]
class GameResult extends AbstractAuditableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'gameResults')]
    #[ORM\JoinColumn(
        name: 'game_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Game $game = null;

    #[ORM\ManyToOne(inversedBy: 'gameResults')]
    #[ORM\JoinColumn(
        name: 'team_id',
        referencedColumnName: 'id',
        nullable: true
    )]
    private ?Team $team = null;

    #[ORM\ManyToOne(inversedBy: 'gameResults')]
    #[ORM\JoinColumn(
        name: 'person_id',
        referencedColumnName: 'id',
        nullable: true
    )]
    private ?Person $person = null;

    #[ORM\Column(nullable: true)]
    private ?int $matchScore = null;

    #[ORM\Column(nullable: true)]
    private ?int $rankingScore = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): static
    {
        $this->game = $game;

        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;

        return $this;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setPerson(?Person $person): static
    {
        $this->person = $person;

        return $this;
    }

    public function getMatchScore(): ?int
    {
        return $this->matchScore;
    }

    public function setMatchScore(?int $matchScore): static
    {
        $this->matchScore = $matchScore;

        return $this;
    }

    public function getRankingScore(): ?int
    {
        return $this->rankingScore;
    }

    public function setRankingScore(?int $rankingScore): static
    {
        $this->rankingScore = $rankingScore;

        return $this;
    }

    public function getOpponent(): ?self
    {
        foreach ($this->game->getGameResults() as $result) {
            if ($result->getId() !== $this->getId()) {
                return $result;
            }
        }
        return null;
    }
}
