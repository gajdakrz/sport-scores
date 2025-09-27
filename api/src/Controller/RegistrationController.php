<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\RegistrationUserRequest;
use App\Form\RegistrationFormType;
use App\Service\RegistrationUserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        RegistrationUserService $registrationService
    ): Response {
        $dto = new RegistrationUserRequest();
        $form = $this->createForm(RegistrationFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $registrationService->register($dto);

            $this->addFlash('success', 'Konto zostało utworzone! Możesz się zalogować.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
