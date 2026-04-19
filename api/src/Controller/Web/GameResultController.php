<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Controller\BaseController;
use App\Dto\Filter\GameResultFilterDto;
use App\Entity\GameResult;
use App\Entity\User;
use App\Enum\MatchResultStatus;
use App\Form\GameResultType;
use App\Repository\GameResultRepository;
use App\Repository\TeamRepository;
use App\Service\CurrentSportProvider;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/game-results')]
final class GameResultController extends BaseController
{
    #[Route('', name: 'game_result_index', methods: ['GET'])]
    public function index(
        #[MapQueryString] GameResultFilterDto $gameResultFilterRequest,
        GameResultRepository $gameResultRepository,
        TeamRepository $teamRepository,
        CurrentSportProvider $currentSportProvider,
    ): Response {
        $currentSport = $currentSportProvider->getSport();
        $queryBuilder = $gameResultRepository->createActiveByFilterBuilder(
            filter: $gameResultFilterRequest,
            sport: $currentSport,
        );

        $pagerfanta = new Pagerfanta(new QueryAdapter($queryBuilder));
        $pagerfanta
            ->setCurrentPage($gameResultFilterRequest->getPage())
            ->setMaxPerPage($gameResultFilterRequest->getLimit());

        return $this->render('game_result/index.html.twig', [
            'gameResults' => $pagerfanta,
            'teams' => $teamRepository->findActiveSortedBy('name', 'ASC', $currentSport),
            'matchResultStatuses' => MatchResultStatus::getValues(),
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
        $currentSport = $currentSportProvider->requireSport();

        $form = $this->createForm(GameResultType::class, $gameResult, [
            'include_game' => true,
            'current_sport' => $currentSport,
        ]);

        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render('game_result/_modal.html.twig', [
                'form' => $form->createView(),
                'gameResult' => $gameResult,
            ]);
        }

        if (!$form->isValid()) {
            $this->throwFormErrors($form);
        }

        $em->persist($gameResult);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Game result created.'], Response::HTTP_CREATED);
    }

    #[Route('/{id}/edit', name: 'game_result_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, GameResult $gameResult, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $gameResult->setModifiedBy($user);
        $form = $this->createForm(GameResultType::class, $gameResult);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render('game_result/_modal.html.twig', [
                'form' => $form->createView(),
                'gameResult' => $gameResult,
            ]);
        }

        if (!$form->isValid()) {
            $this->throwFormErrors($form);
        }

        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Game result updated.']);
    }

    #[Route('/{id}', name: 'game_result_delete', methods: ['POST'])]
    public function delete(Request $request, GameResult $gameResult, EntityManagerInterface $em): Response
    {
        if (
            $response = $this->validateCsrfToken(
                'delete' . $gameResult->getId(),
                (string) $request->request->get('_token')
            )
        ) {
            return $response;
        }

        /** @var User $user */
        $user = $this->getUser();
        $gameResult->setModifiedBy($user);
        $gameResult->setIsActive(false);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Game result deleted.']);
    }
}
