<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Competition;
use App\Entity\User;
use App\Form\CompetitionType;
use App\Repository\CompetitionRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/competition')]
final class CompetitionController extends AbstractController
{
    #[Route('/', name: 'competition_index', methods: ['GET'])]
    public function index(CompetitionRepository $competitionRepository): Response
    {
        return $this->render('competition/index.html.twig', [
            'competitions' => $competitionRepository->findIsActiveSortedBy(),
        ]);
    }

    #[Route('/new', name: 'competition_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $competition = new Competition();
        $competition->setCreatedBy($user);
        $competition->setModifiedBy($user);

        $form = $this->createForm(CompetitionType::class, $competition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($competition);
            $em->flush();
            return $this->redirectToRoute('competition_index');
        }

        return $this->render('competition/_modal.html.twig', [
            'form' => $form->createView(),
            'competition' => $competition,
        ]);
    }

    #[Route('/{id}/edit', name: 'competition_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Competition $competition, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $now = new DateTimeImmutable();
        $competition->setModifiedBy($user);
        $competition->setModifiedAt($now);
        $form = $this->createForm(CompetitionType::class, $competition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('competition_index');
        }

        return $this->render('competition/_modal.html.twig', [
            'form' => $form->createView(),
            'competition' => $competition,
        ]);
    }

    #[Route('/{id}', name: 'competition_delete', methods: ['POST'])]
    public function delete(Request $request, Competition $competition, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('delete' . $competition->getId(), (string) $request->request->get('_token'))) {
            $now = new DateTimeImmutable();
            $competition->setModifiedBy($user);
            $competition->setModifiedAt($now);
            $competition->setIsActive(false);
            $em->flush();
        }
        return $this->redirectToRoute('competition_index');
    }
}
