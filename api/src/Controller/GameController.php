<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\GameFilterRequest;
use App\Entity\Competition;
use App\Entity\Game;
use App\Entity\User;
use App\Form\GameType;
use App\Repository\CompetitionRepository;
use App\Repository\EventRepository;
use App\Repository\GameRepository;
use App\Repository\GameResultRepository;
use App\Repository\SportRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        #[MapQueryString] GameFilterRequest $gameFilterRequest,
        GameRepository $gameRepository,
        SportRepository $sportRepository,
        CompetitionRepository $competitionRepository,
        EventRepository $eventRepository,
    ): Response {
        return $this->render('game/index.html.twig', [
            'games' => $gameRepository->findActiveFilteredSortedBy($gameFilterRequest),
            'sports' => $sportRepository->findActiveSortedBy('name', 'ASC'),
            'competitions' => $competitionRepository->findActiveSortedBy('name', 'ASC'),
            'events' => $eventRepository->findActiveSortedBy('name', 'ASC'),
        ]);
    }

    #[Route('/new', name: 'game_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $game = new Game();
        $game->setCreatedBy($user);
        $game->setModifiedBy($user);

        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $now = new DateTimeImmutable();

            // Obsługa GameResults - ustaw użytkownika dla nowych wyników
            foreach ($game->getGameResults() as $gameResult) {
                if (!$gameResult->getId()) {
                    // Nowy wynik
                    $gameResult->setCreatedBy($user);
                    $gameResult->setModifiedBy($user);
                } else {
                    // Istniejący wynik (aktualizacja)
                    $gameResult->setModifiedBy($user);
                    $gameResult->setModifiedAt($now);
                }

                // Obsługa soft delete
                if (!$gameResult->isActive()) {
                    $gameResult->setModifiedBy($user);
                    $gameResult->setModifiedAt($now);
                }
            }

            $em->persist($game);
            $em->flush();

            return $this->redirectToRoute('game_index');
        }

        return $this->render('game/_modal.html.twig', [
            'form' => $form->createView(),
            'game' => $game,
            'initialSport' => null,
            'initialCompetition' => null,
            'initialEvent' => null,
        ]);
    }

    #[Route('/{id}/edit', name: 'game_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Game $game, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $now = new DateTimeImmutable();
        $game->setModifiedBy($user);
        $game->setModifiedAt($now);

        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Obsługa GameResults
            foreach ($game->getGameResults() as $gameResult) {
                if (!$gameResult->getId()) {
                    // Nowy wynik dodany podczas edycji
                    $gameResult->setCreatedBy($user);
                    $gameResult->setModifiedBy($user);
                } else {
                    // Istniejący wynik (aktualizacja lub soft delete)
                    $gameResult->setModifiedBy($user);
                    $gameResult->setModifiedAt($now);
                }

                // Obsługa soft delete
                if (!$gameResult->isActive()) {
                    $gameResult->setModifiedBy($user);
                    $gameResult->setModifiedAt($now);
                }
            }

            $em->flush();

            return $this->redirectToRoute('game_index');
        }

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

    #[Route('/{id}', name: 'game_delete', methods: ['POST'])]
    public function delete(Request $request, Game $game, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('delete' . $game->getId(), (string) $request->request->get('_token'))) {
            $now = new DateTimeImmutable();
            $game->setModifiedBy($user);
            $game->setModifiedAt($now);
            $game->setIsActive(false);

            foreach ($game->getGameResults() as $gameResult) {
                if ($gameResult->isActive()) {
                    $gameResult->setIsActive(false);
                    $gameResult->setModifiedBy($user);
                    $gameResult->setModifiedAt($now);
                }
            }

            $em->flush();
        }

        return $this->redirectToRoute('game_index');
    }

    #[Route('/{id}/results', name: 'game_results', methods: ['GET'])]
    public function results(Game $game, GameResultRepository $gameResultRepository): Response
    {
        return $this->render('game/_results.html.twig', [
            'gameResults' => $gameResultRepository->findActiveByGame($game),
//            'gameResults' => $gameResultRepository->findBy(['game' => $game]),
        ]);
    }
}
