<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Sport;
use App\Entity\User;
use App\Form\SportType;
use App\Repository\SportRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/sports')]
final class SportController extends AbstractController
{
    #[Route('', name: 'sport_index', methods: ['GET'])]
    public function index(SportRepository $sportRepository): Response
    {
        return $this->render('sport/index.html.twig', [
            'sports' => $sportRepository->findActiveSortedBy(),
        ]);
    }

    #[Route('/new', name: 'sport_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $sport = new Sport();
        $sport->setCreatedBy($user);
        $sport->setModifiedBy($user);

        $form = $this->createForm(SportType::class, $sport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($sport);
            $em->flush();
            return $this->redirectToRoute('sport_index');
        }

        return $this->render('sport/_modal.html.twig', [
            'form' => $form->createView(),
            'sport' => $sport,
        ]);
    }

    #[Route('/{id}/edit', name: 'sport_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Sport $sport, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $now = new DateTimeImmutable();
        $sport->setModifiedBy($user);
        $sport->setModifiedAt($now);
        $form = $this->createForm(SportType::class, $sport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('sport_index');
        }

        return $this->render('sport/_modal.html.twig', [
            'form' => $form->createView(),
            'sport' => $sport,
        ]);
    }

    #[Route('/{id}', name: 'sport_delete', methods: ['POST'])]
    public function delete(Request $request, Sport $sport, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('delete' . $sport->getId(), (string) $request->request->get('_token'))) {
            $now = new DateTimeImmutable();
            $sport->setModifiedBy($user);
            $sport->setModifiedAt($now);
            $sport->setIsActive(false);
            $em->flush();
        }
        return $this->redirectToRoute('sport_index');
    }
}
