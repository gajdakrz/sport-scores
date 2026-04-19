<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Controller\BaseController;
use App\Dto\Filter\EventFilterDto;
use App\Entity\Event;
use App\Entity\User;
use App\Form\EventType;
use App\Repository\CompetitionRepository;
use App\Repository\EventRepository;
use App\Service\CurrentSportProvider;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/events')]
final class EventController extends BaseController
{
    #[Route('', name: 'event_index', methods: ['GET'])]
    public function index(
        #[MapQueryString] EventFilterDto $eventFilterRequest,
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
        $currentSport = $currentSportProvider->requireSport();

        $form = $this->createForm(EventType::class, $event, [
            'current_sport' => $currentSport,
        ]);

        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render('event/_modal.html.twig', [
                'form' => $form->createView(),
                'event' => $event,
                'initialSport' => null,
                'initialCompetition' => null,
            ]);
        }

        if (!$form->isValid()) {
            $this->throwFormErrors($form);
        }

        $em->persist($event);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Event created.'], Response::HTTP_CREATED);
    }

    #[Route('/{id}/edit', name: 'event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $event->setModifiedBy($user);

        $competition = $event->getCompetition();
        $sport = $competition?->getSport();

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render('event/_modal.html.twig', [
                'form' => $form->createView(),
                'event' => $event,
                'initialSport' => $sport,
                'initialCompetition' => $competition,
            ]);
        }

        if (!$form->isValid()) {
            $this->throwFormErrors($form);
        }

        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Event updated.']);
    }

    #[Route('/{id}', name: 'event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event, EntityManagerInterface $em): Response
    {
        if (
            $response = $this->validateCsrfToken(
                'delete' . $event->getId(),
                (string) $request->request->get('_token')
            )
        ) {
            return $response;
        }

        /** @var User $user */
        $user = $this->getUser();
        $event->setModifiedBy($user);
        $event->setIsActive(false);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Event deleted.']);
    }

    #[Route('/by-competition/{competitionId}', name: 'event_by_competition', methods: ['GET'])]
    public function findByCompetition(
        EventRepository $eventRepository,
        CurrentSportProvider $currentSportProvider,
        int $competitionId
    ): JsonResponse {
        $result = [];

        $competition = $eventRepository->find($competitionId);

        if (
            $competition !== null
            && $competition->getCompetition() !== null
            && $currentSportProvider->getSport() !== $competition->getCompetition()->getSport()
        ) {
            return $this->json($result);
        }
        $events = $eventRepository->findBy(['competition' => $competitionId]);
        foreach ($events as $event) {
            $result[] = [
                'id' => $event->getId(),
                'name' => $event->getName(),
            ];
        }

        return $this->json($result);
    }
}
