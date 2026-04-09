<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Season;
use App\Entity\User;
use App\Form\SeasonType;
use App\Repository\SeasonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/seasons')]
final class SeasonController extends BaseController
{
    #[Route('', name: 'season_index', methods: ['GET'])]
    public function index(SeasonRepository $seasonRepository): Response
    {
        return $this->render('season/index.html.twig', [
            'seasons' => $seasonRepository->findActiveSortedBy(),
        ]);
    }

    #[Route('/new', name: 'season_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $season = new Season();
        $season->setCreatedBy($user);
        $season->setModifiedBy($user);

        $form = $this->createForm(SeasonType::class, $season);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render('season/_modal.html.twig', [
                'form' => $form->createView(),
                'season' => $season,
            ]);
        }

        if ($form->isValid()) {
            $em->persist($season);
            $em->flush();
        } else {
            $errors = $form->getErrors(true);

            /** @var FormError $error */
            foreach ($errors as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return new JsonResponse(['success' => true, 'message' => 'Season created.']);
    }

    #[Route('/{id}/edit', name: 'season_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Season $season, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $season->setModifiedBy($user);
        $form = $this->createForm(SeasonType::class, $season);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render('season/_modal.html.twig', [
                'form' => $form->createView(),
                'season' => $season,
            ]);
        }

        if ($form->isValid()) {
            $em->flush();
        } else {
            $errors = $form->getErrors(true);

            /** @var FormError $error */
            foreach ($errors as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return new JsonResponse(['success' => true, 'message' => 'Season updated.']);
    }

    #[Route('/{id}', name: 'season_delete', methods: ['POST'])]
    public function delete(Request $request, Season $season, EntityManagerInterface $em): Response
    {
        if ($response = $this->validateCsrfToken(
            'delete' . $season->getId(),
            (string) $request->request->get('_token')
        )) {
            return $response;
        }

        /** @var User $user */
        $user = $this->getUser();
        $season->setModifiedBy($user);
        $season->setIsActive(false);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Season deleted.']);
    }
}
