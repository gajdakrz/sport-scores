<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Controller\BaseController;
use App\Dto\Filter\TeamDetailFilterDto;
use App\Dto\Filter\TeamFilterDto;
use App\Dto\Filter\TeamGameResultFilterDto;
use App\Dto\Response\TeamGameStatResponseDto;
use App\Entity\Competition;
use App\Entity\Season;
use App\Entity\Team;
use App\Entity\User;
use App\Enum\TeamType as TeamTypeEnum;
use App\Form\TeamType;
use App\Repository\CompetitionRepository;
use App\Repository\CountryRepository;
use App\Repository\GameResultRepository;
use App\Repository\SeasonRepository;
use App\Repository\TeamRepository;
use App\Service\CurrentSportProvider;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/teams')]
final class TeamController extends BaseController
{
    #[Route('', name: 'team_index', methods: ['GET'])]
    public function index(
        #[MapQueryString] TeamFilterDto $teamFilterRequest,
        TeamRepository $teamRepository,
        CountryRepository $countryRepository,
        CurrentSportProvider $currentSportProvider,
    ): Response {
        $queryBuilder = $teamRepository->createActiveByFilterBuilder(
            filter: $teamFilterRequest,
            sport: $currentSportProvider->getSport()
        );

        $pagerfanta = new Pagerfanta(new QueryAdapter($queryBuilder));
        $pagerfanta
            ->setCurrentPage($teamFilterRequest->getPage())
            ->setMaxPerPage($teamFilterRequest->getLimit());

        return $this->render('team/index.html.twig', [
            'teams' => $pagerfanta,
            'teamTypes' => TeamTypeEnum::cases(),
            'countries' => $countryRepository->findActiveSortedBy('name', 'ASC'),
        ]);
    }

    #[Route('/new', name: 'team_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        CurrentSportProvider $currentSportProvider
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $team = new Team();
        $team->setCreatedBy($user);
        $team->setModifiedBy($user);
        $currentSport = $currentSportProvider->requireSport();

        $form = $this->createForm(TeamType::class, $team, [
            'current_sport' => $currentSport,
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render('team/_modal.html.twig', [
                'form' => $form->createView(),
                'team' => $team,
            ]);
        }

        if (!$form->isValid()) {
            $this->throwFormErrors($form);
        }

        $team->setSport($currentSport);
        $em->persist($team);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Team created.'], Response::HTTP_CREATED);
    }

    #[Route('/{id}/edit', name: 'team_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Team $team, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $team->setModifiedBy($user);
        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render('team/_modal.html.twig', [
                'form' => $form->createView(),
                'team' => $team,
            ]);
        }

        if (!$form->isValid()) {
            $this->throwFormErrors($form);
        }

        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Team updated.']);
    }

    #[Route('/{id}', name: 'team_delete', methods: ['POST'])]
    public function delete(Request $request, Team $team, EntityManagerInterface $em): Response
    {
        if (
            $response = $this->validateCsrfToken(
                'delete' . $team->getId(),
                (string) $request->request->get('_token')
            )
        ) {
            return $response;
        }

        /** @var User $user */
        $user = $this->getUser();
        $team->setModifiedBy($user);
        $team->setIsActive(false);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Team deleted.']);
    }

    #[Route(
        '/{team}/seasons/{season}/competitions/{competition}/details',
        name: 'team_season_details',
        methods: ['GET']
    )]
    public function results(
        #[MapQueryString] TeamDetailFilterDto $teamDetailFilterRequest,
        Request $request,
        Team $team,
        Season $season,
        Competition $competition,
        GameResultRepository $gameResultRepository,
    ): Response {
        $queryBuilder = $gameResultRepository->activeByTeamAndSeasonAndCompetitionBuilder(
            $teamDetailFilterRequest,
            $team,
            $season,
            $competition,
            'g.date'
        );

        $pagerfanta = new Pagerfanta(new QueryAdapter($queryBuilder));
        $pagerfanta
            ->setCurrentPage($teamDetailFilterRequest->getPage())
            ->setMaxPerPage($teamDetailFilterRequest->getLimit());

        $result = [
            'season' => $season,
            'team' => $team,
            'competition' => $competition,
            'gameResults' => $pagerfanta,
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('team/_modal_season_details_table.html.twig', $result);
        }

        return $this->render('team/_modal_season_details.html.twig', $result);
    }

    #[Route('/{id}/result-season-index', name: 'team_game_result_season_index', methods: ['GET'])]
    public function resultSeasonIndex(
        #[MapQueryString] TeamGameResultFilterDto $teamGameResultFilterRequest,
        Team $team,
        GameResultRepository $gameResultRepository,
        SeasonRepository $seasonRepository,
        CompetitionRepository $competitionRepository,
        CurrentSportProvider $currentSportProvider
    ): Response {
        $queryBuilder = $gameResultRepository->groupedResultsBySeasonAndCompetitionBuilder(
            team: $team,
            filter: $teamGameResultFilterRequest,
            sport: $currentSportProvider->getSport()
        );

        /** @var TeamGameStatResponseDto[] $resultSeasonStats */
        $resultSeasonStats = $queryBuilder->getQuery()->getResult();

        $pagerfanta = new Pagerfanta(new ArrayAdapter($resultSeasonStats));
        $pagerfanta->setCurrentPage($teamGameResultFilterRequest->getPage());
        $pagerfanta->setMaxPerPage($teamGameResultFilterRequest->getLimit());

        return $this->render('team/result_season_index.html.twig', [
            'team' => $currentSportProvider->getSport() === $team->getSport() ? $team : null,
            'seasons' => $seasonRepository->findActiveSortedBy('startYear'),
            'competitions' => $competitionRepository->findActiveSortedBy('name', 'ASC'),
            'resultSeasonStats' => $pagerfanta,
        ]);
    }
}
