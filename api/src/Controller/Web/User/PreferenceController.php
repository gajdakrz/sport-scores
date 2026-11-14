<?php

declare(strict_types=1);

namespace App\Controller\Web\User;

use App\Controller\BaseController;
use App\Dto\Request\PreferenceRequest;
use App\Entity\User;
use App\Form\PreferenceType;
use App\Repository\UserPreferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/preferences')]
final class PreferenceController extends BaseController
{
    /**
     * The edited preferences always belong to the logged-in user – there is no id in the URL.
     */
    #[Route('/edit', name: 'preference_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        UserPreferenceRepository $userPreferenceRepository,
        EntityManagerInterface $em,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $preference = $userPreferenceRepository->findOrCreateForUser($user);
        $dto = PreferenceRequest::fromPreference($preference);

        $form = $this->createForm(PreferenceType::class, $dto);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render('user/preference/_modal.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        if (!$form->isValid()) {
            $this->throwFormErrors($form);
        }

        $preference->update(
            $dto->getFavouriteSport(),
            $dto->getFavouriteCompetition(),
            $dto->getFavouriteTeam(),
            $dto->isCurrentSeasonByDefault(),
        );
        $em->persist($preference);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Preferences saved.']);
    }
}
