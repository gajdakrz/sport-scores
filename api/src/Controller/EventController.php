<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\EventFilterRequest;
use App\Entity\Event;
use App\Entity\User;
use App\Form\EventType;
use App\Repository\CompetitionRepository;
use App\Repository\EventRepository;
use App\Service\CurrentSportProvider;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/events')]
final class EventController extends AbstractController
{
    #[Route('', name: 'event_index', methods: ['GET'])]
    public function index(
        #[MapQueryString] EventFilterRequest $eventFilterRequest,
        EventRepository $eventRepository,
        CompetitionRepository $competitionRepository,
        CurrentSportProvider $currentSportProvider,
    ): Response {
        $currentSport = $currentSportProvider->getSport();
        $queryBuilder = $eventRepository->createActiveByFilterBuilder(
            filter: $eventFilterRequest,
            sport: $currentSport
        );

        $pagerfanta = new Pagerfanta(new QueryAdapter($queryBuilder));
        $pagerfanta->setCurrentPage($eventFilterRequest->getPage());
        $pagerfanta->setMaxPerPage($eventFilterRequest->getLimit());

        return $this->render('event/index.html.twig', [
            'events' => $pagerfanta,
            'competitions' => $competitionRepository->findActiveSortedBy('name', 'ASC', $currentSport),
        ]);
    }

    #[Route('/new', name: 'event_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        CurrentSportProvider $currentSportProvider
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $event = new Event();
        $event->setCreatedBy($user);
        $event->setModifiedBy($user);

        $currentSport = $currentSportProvider->getSport();

        if (!$currentSport) {
            $this->addFlash('danger', 'Sport not selected');
            return $this->redirectToRoute('event_index');
        }

        $form = $this->createForm(EventType::class, $event, [
            'current_sport' => $currentSport,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($event);
            $em->flush();
            return $this->redirectToRoute('event_index');
        }

        return $this->render('event/_modal.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
            'initialSport' => null,
            'initialCompetition' => null,
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

        $competition = $event->getCompetition();
        $sport = $competition?->getSport();

        return $this->render('event/_modal.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
            'initialSport' => $sport,
            'initialCompetition' => $competition,
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
