<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Controller\BaseController;
use App\Entity\MemberPosition;
use App\Entity\User;
use App\Form\MemberPositionType;
use App\Repository\MemberPositionRepository;
use App\Service\CurrentSportProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/member-positions')]
final class MemberPositionController extends BaseController
{
    #[Route('', name: 'member_position_index', methods: ['GET'])]
    public function index(
        MemberPositionRepository $memberPositionRepository,
        CurrentSportProvider $currentSportProvider
    ): Response {
        return $this->render('member_position/index.html.twig', [
            'memberPositions' => $memberPositionRepository->findActiveSortedBy(
                'name',
                'ASC',
                $currentSportProvider->getSport()
            ),
        ]);
    }

    #[Route('/new', name: 'member_position_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        CurrentSportProvider $currentSportProvider
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $memberPosition = new MemberPosition();
        $memberPosition->setCreatedBy($user);
        $memberPosition->setModifiedBy($user);
        $currentSport = $currentSportProvider->requireSport();

        $form = $this->createForm(MemberPositionType::class, $memberPosition, [
            'current_sport' => $currentSport,
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render('member_position/_modal.html.twig', [
                'form' => $form->createView(),
                'memberPosition' => $memberPosition,
            ]);
        }

        if (!$form->isValid()) {
            $this->throwFormErrors($form);
        }

        $memberPosition->setSport($currentSport);
        $em->persist($memberPosition);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Member position created.'], Response::HTTP_CREATED);
    }

    #[Route('/{id}/edit', name: 'member_position_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MemberPosition $memberPosition, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $memberPosition->setModifiedBy($user);
        $form = $this->createForm(MemberPositionType::class, $memberPosition);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render('member_position/_modal.html.twig', [
                'form' => $form->createView(),
                'memberPosition' => $memberPosition,
            ]);
        }

        if (!$form->isValid()) {
            $this->throwFormErrors($form);
        }

        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Member position updated.']);
    }

    #[Route('/{id}', name: 'member_position_delete', methods: ['POST'])]
    public function delete(Request $request, MemberPosition $memberPosition, EntityManagerInterface $em): Response
    {
        if (
            $response = $this->validateCsrfToken(
                'delete' . $memberPosition->getId(),
                (string) $request->request->get('_token')
            )
        ) {
            return $response;
        }

        /** @var User $user */
        $user = $this->getUser();
        $memberPosition->setModifiedBy($user);
        $memberPosition->setIsActive(false);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Member position deleted.']);
    }
}
