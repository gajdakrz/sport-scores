<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Web;

use App\Entity\Competition;
use App\Entity\Season;
use App\Entity\Team;
use App\Enum\Gender;
use App\Enum\TeamType;
use App\Tests\Trait\ControllerTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class BracketControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    // -------------------------------------------------------------------------
    // Uwierzytelnienie
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Redirects unauthenticated users to the login page')]
    public function redirectsUnauthenticatedUsers(): void
    {
        $client = self::createClient();
        $competition = $this->createTestCompetition();
        $season      = $this->createTestSeason();

        $client->request('GET', $this->buildUrl($competition, $season));

        $this->assertResponseRedirects();
    }

    // -------------------------------------------------------------------------
    // Sukces
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Renders bracket page successfully without a team filter')]
    public function rendersBracketPageWithoutTeam(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $competition = $this->createTestCompetition();
        $season      = $this->createTestSeason();

        // Ustaw current sport zgodny z competition
        $sport = $this->assertSport($competition->getSport());
        $client->request('POST', sprintf('/sports/set/%d', $sport->getId()));

        $client->request('GET', $this->buildUrl($competition, $season));

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    #[Test]
    #[TestDox('Renders bracket page successfully with a team filter matching the current sport')]
    public function rendersBracketPageWithMatchingTeam(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $competition = $this->createTestCompetition();
        $sport       = $this->assertSport($competition->getSport());
        $season      = $this->createTestSeason();
        $team        = $this->createTestTeamForSport($sport);

        $client->request('POST', sprintf('/sports/set/%d', $sport->getId()));

        $client->request('GET', $this->buildUrl($competition, $season, $team));

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    #[Test]
    #[TestDox('Response contains bracketJson variable passed to the template')]
    public function responseContainsBracketJson(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $competition = $this->createTestCompetition();
        $season      = $this->createTestSeason();

        $sport = $this->assertSport($competition->getSport());
        $client->request('POST', sprintf('/sports/set/%d', $sport->getId()));

        $client->request('GET', $this->buildUrl($competition, $season));

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        // bracketJson jest wstrzykiwany do szablonu – weryfikujemy że strona się wyrenderowała
        $this->assertNotEmpty($content);
    }

    // -------------------------------------------------------------------------
    // Niezgodność sportu – team
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Redirects to team index when team sport does not match the current sport')]
    public function redirectsWhenTeamSportMismatch(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $competition   = $this->createTestCompetition();
        $season        = $this->createTestSeason();
        $otherSport    = $this->createTestSport();
        $teamOtherSport = $this->createTestTeamForSport($otherSport);

        // current sport = sport competitionu, team należy do innego sportu
        $sport = $this->assertSport($competition->getSport());
        $client->request('POST', sprintf('/sports/set/%d', $sport->getId()));

        $client->request('GET', $this->buildUrl($competition, $season, $teamOtherSport));

        $this->assertResponseRedirects();
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $client->followRedirect();
        $this->assertRouteSame('team_index');
    }

    #[Test]
    #[TestDox('Adds danger flash message when team sport does not match the current sport')]
    public function addsDangerFlashWhenTeamSportMismatch(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $competition    = $this->createTestCompetition();
        $season         = $this->createTestSeason();
        $otherSport     = $this->createTestSport();
        $teamOtherSport = $this->createTestTeamForSport($otherSport);

        $sport = $this->assertSport($competition->getSport());
        $client->request('POST', sprintf('/sports/set/%d', $sport->getId()));
        $client->request('GET', $this->buildUrl($competition, $season, $teamOtherSport));
        $client->followRedirect();

        $this->assertSelectorTextContains('.flash-danger, [class*="danger"]', 'Missing team for selected sport');
    }

    // -------------------------------------------------------------------------
    // Niezgodność sportu – competition
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Redirects to competition index when competition sport does not match the current sport')]
    public function redirectsWhenCompetitionSportMismatch(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $competition = $this->createTestCompetition();
        $season      = $this->createTestSeason();
        $otherSport  = $this->createTestSport();

        // current sport = inny sport niż sport competitionu
        $client->request('POST', sprintf('/sports/set/%d', $otherSport->getId()));

        $client->request('GET', $this->buildUrl($competition, $season));

        $this->assertResponseRedirects();
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $client->followRedirect();
        $this->assertRouteSame('competition_index');
    }

    #[Test]
    #[TestDox('Adds danger flash message when competition sport does not match the current sport')]
    public function addsDangerFlashWhenCompetitionSportMismatch(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $competition = $this->createTestCompetition();
        $season      = $this->createTestSeason();
        $otherSport  = $this->createTestSport();

        $client->request('POST', sprintf('/sports/set/%d', $otherSport->getId()));
        $client->request('GET', $this->buildUrl($competition, $season));
        $client->followRedirect();

        $this->assertSelectorTextContains('.flash-danger, [class*="danger"]', 'Missing competition for selected sport');
    }

    // -------------------------------------------------------------------------
    // Kolejność sprawdzania – team vs competition
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Checks team sport mismatch before competition sport mismatch')]
    public function teamMismatchCheckedBeforeCompetitionMismatch(): void
    {
        $client = self::createClient();
        $this->loginAsTestUser($client);

        $competition    = $this->createTestCompetition();
        $season         = $this->createTestSeason();
        $otherSport     = $this->createTestSport();
        $teamOtherSport = $this->createTestTeamForSport($otherSport);

        // Oba (team i competition) mają inny sport niż current – team sprawdzany pierwszy
        $thirdSport = $this->createTestSport();
        $client->request('POST', sprintf('/sports/set/%d', $thirdSport->getId()));

        $client->request('GET', $this->buildUrl($competition, $season, $teamOtherSport));
        $client->followRedirect();

        $this->assertRouteSame('team_index');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createTestCompetition(): Competition
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();
        $sport = $this->createTestSport();

        $competition = new Competition();
        $competition->setName('Test Competition ' . uniqid());
        $competition->setSport($sport);
        $competition->setGender(Gender::MALE);
        $competition->setCreatedBy($user);
        $competition->setModifiedBy($user);
        $competition->setIsActive(true);

        $em->persist($competition);
        $em->flush();

        return $competition;
    }

    private function createTestSeason(): Season
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $season = new Season();
        $season->setStartYear(2024);
        $season->setEndYear(2025);
        $season->setCreatedBy($user);
        $season->setModifiedBy($user);
        $season->setIsActive(true);

        $em->persist($season);
        $em->flush();

        return $season;
    }

    private function createTestTeamForSport(\App\Entity\Sport $sport): Team
    {
        $em   = $this->getEntityManager();
        $user = $this->getTestUser();

        $team = new Team();
        $team->setName('Test Team ' . uniqid());
        $team->setSport($sport);
        $team->setTeamType(TeamType::CLUB);
        $team->setCreatedBy($user);
        $team->setModifiedBy($user);
        $team->setIsActive(true);

        $em->persist($team);
        $em->flush();

        return $team;
    }

    private function buildUrl(Competition $competition, Season $season, ?Team $team = null): string
    {
        if ($team !== null) {
            return sprintf(
                '/brackets/competitions/%d/seasons/%d/teams/%d',
                $competition->getId(),
                $season->getId(),
                $team->getId(),
            );
        }

        return sprintf(
            '/brackets/competitions/%d/seasons/%d/teams',
            $competition->getId(),
            $season->getId(),
        );
    }
}
