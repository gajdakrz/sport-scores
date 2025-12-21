<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\GameResult;
use App\Entity\User;
use App\Form\GameResultType;
use App\Repository\GameResultRepository;
use App\Service\CurrentSportProvider;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/game-results')]
final class GameResultController extends AbstractController
{
    #[Route('', name: 'game_result_index', methods: ['GET'])]
    public function index(GameResultRepository $gameResultRepository, CurrentSportProvider $currentSportProvider): Response
    {
        return $this->render('game_result/index.html.twig', [
            'gameResults' => $gameResultRepository->findActiveSortedBy(sport: $currentSportProvider->getSport()),
        ]);
    }

    #[Route('/new', name: 'game_result_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        CurrentSportProvider $currentSportProvider
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $gameResult = new GameResult();
        $gameResult->setCreatedBy($user);
        $gameResult->setModifiedBy($user);
        $currentSport = $currentSportProvider->getSport();

        if (!$currentSport) {
            $this->addFlash('danger', 'Sport not selected');
            return $this->redirectToRoute('event_index');
        }

        $form = $this->createForm(GameResultType::class, $gameResult, [
            'include_game' => true,
            'current_sport' => $currentSport,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($gameResult);
                $em->flush();
            } else {
                $errors = $form->getErrors(true);

                /** @var FormError $error */
                foreach ($errors as $error) {
                    $this->addFlash('danger', $error->getMessage());
                }
            }

            return $this->redirectToRoute('game_result_index');
        }

        return $this->render('game_result/_modal.html.twig', [
            'form' => $form->createView(),
            'gameResult' => $gameResult,
        ]);
    }

    #[Route('/{id}/edit', name: 'game_result_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, GameResult $gameResult, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $now = new DateTimeImmutable();
        $gameResult->setModifiedBy($user);
        $gameResult->setModifiedAt($now);
        $form = $this->createForm(GameResultType::class, $gameResult);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->flush();
            } else {
                $errors = $form->getErrors(true);

                /** @var FormError $error */
                foreach ($errors as $error) {
                    $this->addFlash('danger', $error->getMessage());
                }
            }

            return $this->redirectToRoute('game_result_index');
        }

        return $this->render('game_result/_modal.html.twig', [
            'form' => $form->createView(),
            'gameResult' => $gameResult,
        ]);
    }

    #[Route('/{id}', name: 'game_result_delete', methods: ['POST'])]
    public function delete(Request $request, GameResult $gameResult, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (
            $this->isCsrfTokenValid('delete' . $gameResult->getId(), (string) $request->request->get('_token'))
        ) {
            $now = new DateTimeImmutable();
            $gameResult->setModifiedBy($user);
            $gameResult->setModifiedAt($now);
            $gameResult->setIsActive(false);
            $em->flush();
        }
        return $this->redirectToRoute('game_result_index');
    }
}
