<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Sport;
use App\Repository\SportRepository;
use App\Service\CurrentSportProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CurrentSportProviderTest extends TestCase
{
    private SportRepository&MockObject $sportRepository;
    private SessionInterface&MockObject $session;
    private CurrentSportProvider $provider;

    protected function setUp(): void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $this->sportRepository = $this->createMock(SportRepository::class);
        $this->session = $this->createMock(SessionInterface::class);

        $requestStack
            ->method('getSession')
            ->willReturn($this->session);

        $this->provider = new CurrentSportProvider(
            $requestStack,
            $this->sportRepository,
        );
    }

    #[TestDox('Returns null when no sport ID in session')]
    public function testGetSportReturnsNullWhenNoSportIdInSession(): void
    {
        $this->session->method('get')->with('current_sport_id')->willReturn(null);

        $this->sportRepository->expects($this->never())->method('find');

        $this->assertNull($this->provider->getSport());
    }

    #[TestDox('Returns Sport when sport ID exists in session and sport is found')]
    public function testGetSportReturnsSportWhenFoundById(): void
    {
        $sport = $this->createMock(Sport::class);

        $this->session->method('get')->with('current_sport_id')->willReturn(1);
        $this->sportRepository->method('find')->with(1)->willReturn($sport);

        $this->assertSame($sport, $this->provider->getSport());
    }

    #[TestDox('Returns null when sport ID exists in session but sport is not found in repository')]
    public function testGetSportReturnsNullWhenSportNotFoundInRepository(): void
    {
        $this->session->method('get')->with('current_sport_id')->willReturn(99);
        $this->sportRepository->method('find')->with(99)->willReturn(null);

        $this->assertNull($this->provider->getSport());
    }

    #[TestDox('Returns null from getSportId when no sport in session')]
    public function testGetSportIdReturnsNullWhenNoSportInSession(): void
    {
        $this->session->method('get')->with('current_sport_id')->willReturn(null);

        $this->assertNull($this->provider->getSportId());
    }

    #[TestDox('Returns sport ID when sport exists')]
    public function testGetSportIdReturnsSportId(): void
    {
        $sport = $this->createMock(Sport::class);
        $sport->method('getId')->willReturn(5);

        $this->session->method('get')->with('current_sport_id')->willReturn(5);
        $this->sportRepository->method('find')->with(5)->willReturn($sport);

        $this->assertEquals(5, $this->provider->getSportId());
    }

    #[TestDox('Returns null from getSportName when no sport in session')]
    public function testGetSportNameReturnsNullWhenNoSportInSession(): void
    {
        $this->session->method('get')->with('current_sport_id')->willReturn(null);

        $this->assertNull($this->provider->getSportName());
    }

    #[TestDox('Returns sport name when sport exists')]
    public function testGetSportNameReturnsSportName(): void
    {
        $sport = $this->createMock(Sport::class);
        $sport->method('getName')->willReturn('Football');

        $this->session->method('get')->with('current_sport_id')->willReturn(3);
        $this->sportRepository->method('find')->with(3)->willReturn($sport);

        $this->assertEquals('Football', $this->provider->getSportName());
    }
}
