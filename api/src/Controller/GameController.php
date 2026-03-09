<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Filter\GameFilterDto;
use App\Entity\Game;
use App\Entity\User;
use App\Enum\Gender;
use App\Form\GameType;
use App\Repository\CompetitionRepository;
use App\Repository\EventRepository;
use App\Repository\GameRepository;
use App\Repository\GameResultRepository;
use App\Repository\SeasonRepository;
use App\Service\CurrentSportProvider;
use App\Service\GameResultHandler;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/games')]
final class GameController extends AbstractController
{
    #[Route('', name: 'game_index', methods: ['GET'])]
    public function index(
        #[MapQueryString] GameFilterDto $gameFilterRequest,
        GameRepository $gameRepository,
        CompetitionRepository $competitionRepository,
        EventRepository $eventRepository,
        SeasonRepository $seasonRepository,
        CurrentSportProvider $currentSportProvider,
    ): Response {
        $currentSport = $currentSportProvider->getSport();
        $queryBuilder = $gameRepository->createActiveByFilterBuilder(
            filter: $gameFilterRequest,
            sport: $currentSport
        );

        $pagerfanta = new Pagerfanta(new QueryAdapter($queryBuilder));
        $pagerfanta
            ->setCurrentPage($gameFilterRequest->getPage())
            ->setMaxPerPage($gameFilterRequest->getLimit());

        return $this->render('game/index.html.twig', [
            'games' => $pagerfanta,
            'competitions' => $competitionRepository->findActiveSortedBy('name', 'ASC', $currentSport),
            'events' => $eventRepository->findActiveSortedBy('name', 'ASC'),
            'seasons' => $seasonRepository->findActiveSortedBy('startYear'),
            'genders' => Gender::cases(),
        ]);
    }

    #[Route('/new', name: 'game_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        CurrentSportProvider $currentSportProvider,
        GameResultHandler $gameResultHandler
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $game = new Game();
        $game->setCreatedBy($user);
        $game->setModifiedBy($user);
        $currentSport = $currentSportProvider->getSport();

        if (!$currentSport) {
            $this->addFlash('danger', 'Sport not selected');
            return $this->redirectToRoute('game_index');
        }

        $form = $this->createForm(GameType::class, $game, [
            'current_sport' => $currentSport,
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render('game/_modal.html.twig', [
                'form' => $form->createView(),
                'game' => $game,
                'initialSport' => null,
                'initialCompetition' => null,
                'initialEvent' => null,
            ]);
        }

        if ($form->isValid()) {
            $gameResultHandler->handle($game, $user);
            $em->persist($game);
            $em->flush();
        } else {
            /** @var FormError $error */
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }
        $this->addFlash('success', 'Game created.');

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/edit', name: 'game_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Game $game,
        EntityManagerInterface $em,
        GameResultHandler $gameResultHandler
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $game->setModifiedBy($user);

        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            $event = $game->getEvent();
            $competition = $event?->getCompetition();
            $sport = $competition?->getSport();

            return $this->render('game/_modal.html.twig', [
                'form' => $form->createView(),
                'game' => $game,
                'initialSport' => $sport,
                'initialCompetition' => $competition,
                'initialEvent' => $event,
            ]);
        }

        if ($form->isValid()) {
            $gameResultHandler->handle($game, $user);
            $em->persist($game);
            $em->flush();
        } else {
            /** @var FormError $error */
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }
        $this->addFlash('success', 'Game updated.');

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}', name: 'game_delete', methods: ['POST'])]
    public function delete(Request $request, Game $game, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('delete' . $game->getId(), (string) $request->request->get('_token'))) {
            $game->setModifiedBy($user);
            $game->setIsActive(false);

            foreach ($game->getGameResults() as $gameResult) {
                if ($gameResult->isActive()) {
                    $gameResult->setIsActive(false);
                    $gameResult->setModifiedBy($user);
                }
            }

            $em->flush();
        }
        $this->addFlash('success', 'Game deleted.');

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/results', name: 'game_results', methods: ['GET'])]
    public function results(Game $game, GameResultRepository $gameResultRepository): Response
    {
        return $this->render('game/_results.html.twig', [
            'gameResults' => $gameResultRepository->findActiveByGame($game),
        ]);
    }
}
