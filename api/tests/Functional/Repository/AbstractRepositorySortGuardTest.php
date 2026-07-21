<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Dto\Filter\TeamDetailFilterDto;
use App\Entity\Team;
use App\Repository\GameResultRepository;
use App\Repository\SportRepository;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AbstractRepositorySortGuardTest extends KernelTestCase
{
    #[Test]
    #[TestDox('Throws when orderBy field is not a real mapped field on the entity')]
    public function throwsForInvalidSortField(): void
    {
        self::bootKernel();
        /** @var SportRepository $repository */
        $repository = self::getContainer()->get(SportRepository::class);

        $this->expectException(InvalidArgumentException::class);

        $repository->findActiveSortedBy('id; DROP TABLE sport; --');
    }

    #[Test]
    #[TestDox('Throws when sort direction is not ASC or DESC')]
    public function throwsForInvalidSortDirection(): void
    {
        self::bootKernel();
        /** @var SportRepository $repository */
        $repository = self::getContainer()->get(SportRepository::class);

        $this->expectException(InvalidArgumentException::class);

        $repository->findActiveSortedBy('name', 'INVALID');
    }

    #[Test]
    #[TestDox('Throws when a full sort expression is not on the explicit whitelist')]
    public function throwsForDisallowedSortExpression(): void
    {
        self::bootKernel();
        /** @var GameResultRepository $repository */
        $repository = self::getContainer()->get(GameResultRepository::class);

        $this->expectException(InvalidArgumentException::class);

        $repository->activeByTeamAndSeasonAndCompetitionBuilder(
            new TeamDetailFilterDto(),
            new Team(),
            null,
            null,
            'gr1.someUnexpectedField'
        );
    }

    #[Test]
    #[TestDox('Accepts a valid sort field and direction on GameResultRepository')]
    public function acceptsValidSortOnGameResultRepository(): void
    {
        self::bootKernel();
        /** @var GameResultRepository $repository */
        $repository = self::getContainer()->get(GameResultRepository::class);

        $this->expectNotToPerformAssertions();

        $repository->findActiveSortedBy('createdAt', 'ASC');
    }
}
