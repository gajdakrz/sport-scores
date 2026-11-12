<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Controller\BaseController;
use App\Dto\Request\ProfileRequest;
use App\Entity\User;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/profile')]
final class ProfileController extends BaseController
{
    /**
     * The edited account is always the logged-in user – there is no id in the URL.
     */
    #[Route('/edit', name: 'profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $dto = ProfileRequest::fromUser($user);

        $form = $this->createForm(ProfileType::class, $dto);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render('profile/_modal.html.twig', [
                'form' => $form->createView(),
                'user' => $user,
            ]);
        }

        if (!$form->isValid()) {
            $this->throwFormErrors($form);
        }

        $user->changeName($dto->getFirstName(), $dto->getLastName());
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Profile updated.']);
    }
}
