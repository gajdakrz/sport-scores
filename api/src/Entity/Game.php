<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game Extends AbstractAuditableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'games')]
    #[ORM\JoinColumn(
        name: 'event_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private Event $event;

    public function getId(): int
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

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): static
    {
        $this->event = $event;

        return $this;
    }
}
