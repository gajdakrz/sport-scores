<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Preference;

use App\Entity\Competition;
use App\Entity\Sport;
use App\Entity\Team;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Repository\UserPreferenceRepository;
use App\Service\CurrentSportProvider;
use App\Service\Preference\CurrentSeasonFilterDefaults;
use App\Service\Preference\FavouriteCompetitionFilterDefaults;
use App\Service\Preference\FavouriteTeamFilterDefaults;
use App\Service\Preference\PreferredFilterResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PreferredFilterResolverTest extends TestCase
{
    private const string GAMES = 'game_index';
    private const string GAME_RESULTS = 'game_result_index';
    private const string TEAM_MEMBERS = 'team_member_index';
    private const string TEAMS = 'team_index';
    private const string EVENTS = 'event_index';
    private const string TEAM_SEASON_STATS = 'team_game_result_season_index';
    private const int COMPETITION_ID = 3;
    private const int TEAM_ID = 7;
    private const string TEAM_NAME = 'Favourite FC';

    private User $user;
    private Sport $sport;

    protected function setUp(): void
    {
        $this->user = (new User())->setId(1);
        $this->sport = (new Sport())->setId(1);
    }

    #[Test]
    #[TestDox('Without a logged-in user Games open with "Current season" and nothing else is preset')]
    public function anonymousGetsAppDefaults(): void
    {
        $repository = $this->createMock(UserPreferenceRepository::class);
        $repository->expects($this->never())->method('findOrCreateForUser');
        $resolver = $this->createResolver(null, $this->sport, $repository);

        $this->assertSame(['currentSeason' => 1], $resolver->resolve(self::GAMES));
        $this->assertSame([], $resolver->resolve(self::GAME_RESULTS));
    }

    #[Test]
    #[TestDox('Games open without "Current season" when the user switched it off')]
    public function currentSeasonSwitchedOffIsNotPreset(): void
    {
        $resolver = $this->createResolverFor($this->createPreference(null, false), $this->sport);

        $this->assertSame([], $resolver->resolve(self::GAMES));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function teamFilteredRouteProvider(): array
    {
        return [
            'game results' => [self::GAME_RESULTS],
            'team members' => [self::TEAM_MEMBERS],
        ];
    }

    #[Test]
    #[DataProvider('teamFilteredRouteProvider')]
    #[TestDox('List of $_dataName is filtered by the favourite team of the current sport')]
    public function favouriteTeamOfCurrentSportIsPreset(string $route): void
    {
        $preference = $this->createPreference($this->createTeam($this->sport));
        $resolver = $this->createResolverFor($preference, $this->sport);

        $this->assertSame(['teamId' => self::TEAM_ID], $resolver->resolve($route));
    }

    #[Test]
    #[TestDox('Teams list is filtered by the name of the favourite team of the current sport')]
    public function favouriteTeamNameIsPresetOnTeams(): void
    {
        $preference = $this->createPreference($this->createTeam($this->sport));
        $resolver = $this->createResolverFor($preference, $this->sport);

        $this->assertSame(['name' => self::TEAM_NAME], $resolver->resolve(self::TEAMS));
    }

    #[Test]
    #[TestDox('Favourite team name is not preset on Teams while another sport is the current one')]
    public function favouriteTeamNameOfAnotherSportIsNotPreset(): void
    {
        $preference = $this->createPreference($this->createTeam($this->sport));
        $resolver = $this->createResolverFor($preference, (new Sport())->setId(2));

        $this->assertSame([], $resolver->resolve(self::TEAMS));
    }

    /**
     * @return array<string, array{string, array<string, int>}>
     */
    public static function competitionFilteredRouteProvider(): array
    {
        return [
            'games'             => [self::GAMES, ['currentSeason' => 1, 'competitionId' => self::COMPETITION_ID]],
            'events'            => [self::EVENTS, ['competitionId' => self::COMPETITION_ID]],
            'team season stats' => [self::TEAM_SEASON_STATS, ['competitionId' => self::COMPETITION_ID]],
        ];
    }

    /**
     * @param array<string, int> $expected
     */
    #[Test]
    #[DataProvider('competitionFilteredRouteProvider')]
    #[TestDox('List of $_dataName is filtered by the favourite competition of the current sport')]
    public function favouriteCompetitionOfCurrentSportIsPreset(string $route, array $expected): void
    {
        $preference = $this->createPreference(null, true, $this->createCompetition($this->sport));
        $resolver = $this->createResolverFor($preference, $this->sport);

        $this->assertSame($expected, $resolver->resolve($route));
    }

    #[Test]
    #[TestDox('Favourite competition is not preset while another sport is the current one')]
    public function favouriteCompetitionOfAnotherSportIsNotPreset(): void
    {
        $preference = $this->createPreference(null, false, $this->createCompetition($this->sport));
        $resolver = $this->createResolverFor($preference, (new Sport())->setId(2));

        $this->assertSame([], $resolver->resolve(self::EVENTS));
        $this->assertSame([], $resolver->resolve(self::GAMES));
    }

    #[Test]
    #[TestDox('Route parameters are kept next to the preset filters')]
    public function routeParametersAreMergedWithPresetFilters(): void
    {
        $preference = $this->createPreference(null, true, $this->createCompetition($this->sport));
        $repository = $this->createStub(UserPreferenceRepository::class);
        $repository->method('findOrCreateForUser')->willReturn($preference);
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->once())
            ->method('generate')
            ->with(self::TEAM_SEASON_STATS, ['id' => 5, 'competitionId' => self::COMPETITION_ID])
            ->willReturn('/teams/5/result-season-index?competitionId=3');
        $resolver = $this->createResolver($this->user, $this->sport, $repository, $urlGenerator);

        $this->assertSame(
            '/teams/5/result-season-index?competitionId=3',
            $resolver->generatePath(self::TEAM_SEASON_STATS, ['id' => 5])
        );
    }

    #[Test]
    #[TestDox('Favourite team is not preset while another sport is the current one')]
    public function favouriteTeamOfAnotherSportIsNotPreset(): void
    {
        $preference = $this->createPreference($this->createTeam($this->sport));
        $resolver = $this->createResolverFor($preference, (new Sport())->setId(2));

        $this->assertSame([], $resolver->resolve(self::GAME_RESULTS));
    }

    #[Test]
    #[TestDox('Deactivated favourite team is not preset')]
    public function deactivatedFavouriteTeamIsNotPreset(): void
    {
        $preference = $this->createPreference($this->createTeam($this->sport)->setIsActive(false));
        $resolver = $this->createResolverFor($preference, $this->sport);

        $this->assertSame([], $resolver->resolve(self::GAME_RESULTS));
    }

    #[Test]
    #[TestDox('Favourite team is not preset when no sport is selected')]
    public function favouriteTeamWithoutCurrentSportIsNotPreset(): void
    {
        $preference = $this->createPreference($this->createTeam($this->sport));
        $resolver = $this->createResolverFor($preference, null);

        $this->assertSame([], $resolver->resolve(self::TEAM_MEMBERS));
    }

    #[Test]
    #[TestDox('Path is generated with the preset filters and the preferences are loaded once per request')]
    public function pathIsGeneratedWithPresetFilters(): void
    {
        $repository = $this->createMock(UserPreferenceRepository::class);
        $repository->expects($this->once())
            ->method('findOrCreateForUser')
            ->willReturn(new UserPreference($this->user));
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->exactly(2))
            ->method('generate')
            ->with(self::GAMES, ['currentSeason' => 1])
            ->willReturn('/games?currentSeason=1');
        $resolver = $this->createResolver($this->user, $this->sport, $repository, $urlGenerator);

        $firstPath = $resolver->generatePath(self::GAMES);
        $secondPath = $resolver->generatePath(self::GAMES);

        $this->assertSame('/games?currentSeason=1', $firstPath);
        $this->assertSame('/games?currentSeason=1', $secondPath);
    }

    #[Test]
    #[TestDox('Preferences are loaded again after a reset (next request)')]
    public function preferencesAreReloadedAfterReset(): void
    {
        $repository = $this->createMock(UserPreferenceRepository::class);
        $repository->expects($this->exactly(2))
            ->method('findOrCreateForUser')
            ->willReturn(new UserPreference($this->user));
        $resolver = $this->createResolver($this->user, $this->sport, $repository);

        $resolver->resolve(self::GAMES);
        $resolver->reset();
        $resolver->resolve(self::GAMES);
    }

    private function createResolverFor(UserPreference $preference, ?Sport $currentSport): PreferredFilterResolver
    {
        $repository = $this->createStub(UserPreferenceRepository::class);
        $repository->method('findOrCreateForUser')->willReturn($preference);

        return $this->createResolver($this->user, $currentSport, $repository);
    }

    private function createResolver(
        ?User $user,
        ?Sport $currentSport,
        UserPreferenceRepository $repository,
        ?UrlGeneratorInterface $urlGenerator = null,
    ): PreferredFilterResolver {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);
        $currentSportProvider = $this->createStub(CurrentSportProvider::class);
        $currentSportProvider->method('getSport')->willReturn($currentSport);

        return new PreferredFilterResolver(
            [
                new CurrentSeasonFilterDefaults(),
                new FavouriteCompetitionFilterDefaults(),
                new FavouriteTeamFilterDefaults(),
            ],
            $security,
            $repository,
            $currentSportProvider,
            $urlGenerator ?? $this->createStub(UrlGeneratorInterface::class),
        );
    }

    private function createPreference(
        ?Team $favouriteTeam,
        bool $currentSeasonByDefault = true,
        ?Competition $favouriteCompetition = null,
    ): UserPreference {
        return (new UserPreference($this->user))->update(
            $favouriteTeam?->getSport() ?? $favouriteCompetition?->getSport(),
            $favouriteCompetition,
            $favouriteTeam,
            $currentSeasonByDefault,
        );
    }

    private function createCompetition(Sport $sport): Competition
    {
        return (new Competition())->setId(self::COMPETITION_ID)->setSport($sport);
    }

    private function createTeam(Sport $sport): Team
    {
        return (new Team())->setId(self::TEAM_ID)->setName(self::TEAM_NAME)->setSport($sport);
    }
}
