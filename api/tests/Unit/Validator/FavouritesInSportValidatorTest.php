<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Dto\Request\PreferenceRequest;
use App\Entity\Competition;
use App\Entity\Sport;
use App\Entity\Team;
use App\Validator\FavouritesInSport;
use App\Validator\FavouritesInSportValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class FavouritesInSportValidatorTest extends TestCase
{
    private const int SPORT_ID = 1;
    private const int OTHER_SPORT_ID = 2;
    private const string COMPETITION = 'favouriteCompetition';
    private const string TEAM = 'favouriteTeam';

    private FavouritesInSportValidator $validator;
    private ExecutionContextInterface&MockObject $context;

    protected function setUp(): void
    {
        $this->validator = new FavouritesInSportValidator();
        $this->context = $this->createMock(ExecutionContextInterface::class);
    }

    #[Test]
    #[TestDox('Throws UnexpectedTypeException for another constraint')]
    public function throwsExceptionForInvalidConstraintType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validateInContext(
            new PreferenceRequest(),
            $this->createStub(Constraint::class),
            $this->context
        );
    }

    /**
     * @return array<string, array{?int, ?int, ?int}> sport id, competition's sport id, team's sport id (null = unset)
     */
    public static function validPreferencesProvider(): array
    {
        return [
            'no favourites'                           => [null, null, null],
            'sport only'                              => [self::SPORT_ID, null, null],
            'competition and team of the sport'       => [self::SPORT_ID, self::SPORT_ID, self::SPORT_ID],
        ];
    }

    #[Test]
    #[DataProvider('validPreferencesProvider')]
    #[TestDox('Preferences with $_dataName are accepted')]
    public function validPreferencesAreAccepted(?int $sportId, ?int $competitionSportId, ?int $teamSportId): void
    {
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validateInContext(
            $this->createRequest($sportId, $competitionSportId, $teamSportId),
            new FavouritesInSport(),
            $this->context
        );
    }

    /**
     * @return array<string, array{?int, ?int, ?int, string}> as above + the rejected field
     */
    public static function invalidPreferencesProvider(): array
    {
        return [
            'competition without favourite sport' => [null, self::SPORT_ID, null, self::COMPETITION],
            'competition of another sport'        => [self::SPORT_ID, self::OTHER_SPORT_ID, null, self::COMPETITION],
            'team without favourite sport'        => [null, null, self::SPORT_ID, self::TEAM],
            'team of another sport'               => [self::SPORT_ID, self::SPORT_ID, self::OTHER_SPORT_ID, self::TEAM],
        ];
    }

    #[Test]
    #[DataProvider('invalidPreferencesProvider')]
    #[TestDox('Preferences with a $_dataName are rejected on that field')]
    public function favouriteOutsideFavouriteSportIsRejected(
        ?int $sportId,
        ?int $competitionSportId,
        ?int $teamSportId,
        string $field,
    ): void {
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())->method('atPath')->with($field)->willReturnSelf();
        $builder->expects($this->once())->method('addViolation');
        $this->context->expects($this->once())->method('buildViolation')->willReturn($builder);

        $this->validator->validateInContext(
            $this->createRequest($sportId, $competitionSportId, $teamSportId),
            new FavouritesInSport(),
            $this->context
        );
    }

    private function createRequest(?int $sportId, ?int $competitionSportId, ?int $teamSportId): PreferenceRequest
    {
        $competition = $competitionSportId === null
            ? null
            : (new Competition())->setSport($this->createSport($competitionSportId));
        $team = $teamSportId === null
            ? null
            : (new Team())->setSport($this->createSport($teamSportId));

        return (new PreferenceRequest())
            ->setFavouriteSport($sportId === null ? null : $this->createSport($sportId))
            ->setFavouriteCompetition($competition)
            ->setFavouriteTeam($team);
    }

    private function createSport(int $id): Sport
    {
        return (new Sport())->setId($id);
    }
}
