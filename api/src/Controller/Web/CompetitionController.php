<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Controller\BaseController;
use App\Entity\Competition;
use App\Entity\User;
use App\Form\CompetitionType;
use App\Repository\CompetitionRepository;
use App\Service\CurrentSportProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/competitions')]
final class CompetitionController extends BaseController
{
    #[Route('', name: 'competition_index', methods: ['GET'])]
    public function index(
        CompetitionRepository $competitionRepository,
        CurrentSportProvider $currentSportProvider
    ): Response {
        return $this->render('competition/index.html.twig', [
            'competitions' => $competitionRepository->findActiveSortedBy(
                'name',
                'ASC',
                $currentSportProvider->getSport()
            ),
        ]);
    }

    #[Route('/new', name: 'competition_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        CurrentSportProvider $currentSportProvider
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $competition = new Competition();
        $competition->setCreatedBy($user);
        $competition->setModifiedBy($user);
        $currentSport = $currentSportProvider->requireSport();

        $form = $this->createForm(CompetitionType::class, $competition, [
            'current_sport' => $currentSport,
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render('competition/_modal.html.twig', [
                'form' => $form->createView(),
                'competition' => $competition,
            ]);
        }

        if (!$form->isValid()) {
            $this->throwFormErrors($form);
        }

        $competition->setSport($currentSport);
        $em->persist($competition);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Competition created.'], Response::HTTP_CREATED);

    }

    #[Route('/{id}/edit', name: 'competition_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Competition $competition, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $competition->setModifiedBy($user);
        $form = $this->createForm(CompetitionType::class, $competition);
        $form->handleRequest($request);


        if (!$form->isSubmitted()) {
            return $this->render('competition/_modal.html.twig', [
                'form' => $form->createView(),
                'competition' => $competition,
            ]);
        }

        if (!$form->isValid()) {
            $this->throwFormErrors($form);
        }

        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Competition updated.']);
    }

    #[Route('/{id}', name: 'competition_delete', methods: ['POST'])]
    public function delete(Request $request, Competition $competition, EntityManagerInterface $em): Response
    {
        if (
            $response = $this->validateCsrfToken(
                'delete' . $competition->getId(),
                (string) $request->request->get('_token')
            )
        ) {
            return $response;
        }

        /** @var User $user */
        $user = $this->getUser();
        $competition->setModifiedBy($user);
        $competition->setIsActive(false);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Competition deleted.']);
    }

    #[Route('/by-sport/{sportId}', name: 'competition_by_sport', methods: ['GET'])]
    public function bySport(CompetitionRepository $competitionRepository, int $sportId): JsonResponse
    {
        $competitions = $competitionRepository->findBy(['sport' => $sportId]);
        $result = [];
        foreach ($competitions as $competition) {
            $result[] = [
                'id' => $competition->getId(),
                'name' => $competition->getName(),
            ];
        }

        return $this->json($result);
    }
}
