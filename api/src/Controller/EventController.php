<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Form\EventType;
use App\Repository\EventRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/events')]
final class EventController extends AbstractController
{
    #[Route('', name: 'event_index', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        return $this->render('event/index.html.twig', [
            'events' => $eventRepository->findActiveSortedBy(),
        ]);
    }

    #[Route('/new', name: 'event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $event = new Event();
        $event->setCreatedBy($user);
        $event->setModifiedBy($user);

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($event);
            $em->flush();
            return $this->redirectToRoute('event_index');
        }

        return $this->render('event/_modal.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }

    #[Route('/{id}/edit', name: 'event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $now = new DateTimeImmutable();
        $event->setModifiedBy($user);
        $event->setModifiedAt($now);
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('event_index');
        }

        return $this->render('event/_modal.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }

    #[Route('/{id}', name: 'event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('delete' . $event->getId(), (string) $request->request->get('_token'))) {
            $now = new DateTimeImmutable();
            $event->setModifiedBy($user);
            $event->setModifiedAt($now);
            $event->setIsActive(false);
            $em->flush();
        }
        return $this->redirectToRoute('event_index');
    }

    #[Route('/by-competition/{competitionId}', name: 'event_by_competition', methods: ['GET'])]
    public function byCompetition(EventRepository $eventRepository, int $competitionId): JsonResponse
    {
        $events = $eventRepository->findBy(['competition' => $competitionId]);
        $result = [];
        foreach ($events as $event) {
            $result[] = [
                'id' => $event->getId(),
                'name' => $event->getName(),
            ];
        }

        return $this->json($result);
    }
}
