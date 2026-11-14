<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserPreferenceRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Optional per-user settings. Unidirectional (no inverse side on User), so the preferences
 * are never loaded with – or serialized into the session together with – the security user.
 */
#[ORM\Entity(repositoryClass: UserPreferenceRepository::class)]
class UserPreference extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Sport::class)]
    #[ORM\JoinColumn(name: 'favourite_sport_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Sport $favouriteSport = null;

    #[ORM\ManyToOne(targetEntity: Competition::class)]
    #[ORM\JoinColumn(
        name: 'favourite_competition_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?Competition $favouriteCompetition = null;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(name: 'favourite_team_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Team $favouriteTeam = null;

    /**
     * Whether links to Games open with the "Current season" filter on (the app's default).
     */
    #[ORM\Column(options: ['default' => true])]
    private bool $currentSeasonByDefault = true;

    public function __construct(User $user)
    {
        $this->user = $user;
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

    public function getUser(): User
    {
        return $this->user;
    }

    public function getFavouriteSport(): ?Sport
    {
        return $this->favouriteSport;
    }

    public function getFavouriteCompetition(): ?Competition
    {
        return $this->favouriteCompetition;
    }

    public function getFavouriteTeam(): ?Team
    {
        return $this->favouriteTeam;
    }

    public function isCurrentSeasonByDefault(): bool
    {
        return $this->currentSeasonByDefault;
    }

    public function update(
        ?Sport $favouriteSport,
        ?Competition $favouriteCompetition,
        ?Team $favouriteTeam,
        bool $currentSeasonByDefault,
    ): static {
        $this->favouriteSport = $favouriteSport;
        $this->favouriteCompetition = $favouriteCompetition;
        $this->favouriteTeam = $favouriteTeam;
        $this->currentSeasonByDefault = $currentSeasonByDefault;

        return $this;
    }

    /**
     * The favourite sport, unless it has been deactivated since it was chosen.
     */
    public function getActiveFavouriteSport(): ?Sport
    {
        return $this->favouriteSport?->isActive() ? $this->favouriteSport : null;
    }

    /**
     * The favourite competition, but only while it is active and the given sport is its sport.
     */
    public function getFavouriteCompetitionFor(?Sport $sport): ?Competition
    {
        return self::isInSport($this->favouriteCompetition, $sport) ? $this->favouriteCompetition : null;
    }

    /**
     * The favourite team, but only while it is active and the given sport is the team's sport.
     */
    public function getFavouriteTeamFor(?Sport $sport): ?Team
    {
        return self::isInSport($this->favouriteTeam, $sport) ? $this->favouriteTeam : null;
    }

    private static function isInSport(?SportScopedInterface $favourite, ?Sport $sport): bool
    {
        if ($favourite === null || $sport === null || !$favourite->isActive()) {
            return false;
        }

        return $favourite->getSport()?->getId() === $sport->getId();
    }
}
