<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\PaginationRequest;
use App\Dto\TeamDetailFilterRequest;
use App\Dto\TeamFilterRequest;
use App\Entity\Competition;
use App\Entity\Season;
use App\Entity\Team;
use App\Entity\User;
use App\Form\TeamType;
use App\Enum\TeamType as TeamTypeEnum;
use App\Repository\CountryRepository;
use App\Repository\GameResultRepository;
use App\Repository\TeamRepository;
use App\Service\CurrentSportProvider;
use App\Service\PaginationService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/teams')]
final class TeamController extends AbstractController
{
    #[Route('', name: 'team_index', methods: ['GET'])]
    public function index(
        #[MapQueryString] TeamFilterRequest $teamFilterRequest,
        TeamRepository $teamRepository,
        CountryRepository $countryRepository,
        CurrentSportProvider $currentSportProvider,
        PaginationService $paginationService
    ): Response {
        $paginator = $teamRepository->findActivePaginatedByFilter(
            filter: $teamFilterRequest,
            sport: $currentSportProvider->getSport()
        );

        return $this->render('team/index.html.twig', [
            'teams' => $paginator,
            'pagination' => $paginationService->getPaginationData($teamFilterRequest, $paginator),
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
        $currentSport = $currentSportProvider->getSport();

        if (!$currentSport) {
            $this->addFlash('danger', 'Sport not selected');
            return $this->redirectToRoute('team_index');
        }

        $form = $this->createForm(TeamType::class, $team, [
            'current_sport' => $currentSport,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($team);
            $em->flush();
            return $this->redirectToRoute('team_index');
        }

        return $this->render('team/_modal.html.twig', [
            'form' => $form->createView(),
            'team' => $team,
        ]);
    }

    #[Route('/{id}/edit', name: 'team_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Team $team, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $now = new DateTimeImmutable();
        $team->setModifiedBy($user);
        $team->setModifiedAt($now);
        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('team_index');
        }

        return $this->render('team/_modal.html.twig', [
            'form' => $form->createView(),
            'team' => $team,
        ]);
    }

    #[Route('/{id}', name: 'team_delete', methods: ['POST'])]
    public function delete(Request $request, Team $team, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('delete' . $team->getId(), (string) $request->request->get('_token'))) {
            $now = new DateTimeImmutable();
            $team->setModifiedBy($user);
            $team->setModifiedAt($now);
            $team->setIsActive(false);
            $em->flush();
        }
        return $this->redirectToRoute('team_index');
    }

    #[Route(
        '/{team}/seasons/{season}/competitions/{competition}/details',
        name: 'team_season_details',
        methods: ['GET']
    )]
    public function results(
        #[MapQueryString] TeamDetailFilterRequest $teamDetailFilterRequest,
        Request $request,
        Team $team,
        Season $season,
        Competition $competition,
        GameResultRepository $gameResultRepository,
        PaginationService $paginationService
    ): Response {
        $paginator = $gameResultRepository->findActiveByTeamAndSeasonPaginated(
            $teamDetailFilterRequest,
            $team,
            $season,
            $competition,
            'g.date'
        );

        $result = [
            'season' => $season,
            'team' => $team,
            'competition' => $competition,
            'gameResults' => $paginator,
            'pagination' => $paginationService->getPaginationData($teamDetailFilterRequest, $paginator),
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('team/_modal_season-details-table.html.twig', $result);
        }

        return $this->render('team/_modal_season-details.html.twig', $result);
    }

    #[Route('/{id}/result-season-stats', name: 'team_game_result_season_stats', methods: ['GET'])]
    public function resultSeasonStats(
        Team $team,
        GameResultRepository $gameResultRepository
    ): Response {
        return $this->render('team/_result-season-stats.html.twig', [
            'resultSeasonStats' => $gameResultRepository->getResultsGroupedBySeasonAndCompetition($team)
        ]);
    }
}
