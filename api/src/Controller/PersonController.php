<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Filter\PersonFilterDto;
use App\Entity\Person;
use App\Entity\User;
use App\Enum\Gender;
use App\Enum\TeamFilter;
use App\Form\PersonType;
use App\Repository\CountryRepository;
use App\Repository\PersonRepository;
use App\Service\CurrentSportProvider;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/persons')]
final class PersonController extends BaseController
{
    #[Route('', name: 'person_index', methods: ['GET'])]
    public function index(
        #[MapQueryString] PersonFilterDto $personFilterRequest,
        PersonRepository $personRepository,
        CountryRepository $countryRepository,
        CurrentSportProvider $currentSportProvider
    ): Response {

        $currentSport = $currentSportProvider->getSport();
        $queryBuilder = $personRepository->createActiveByFilterBuilder(
            filter: $personFilterRequest,
            sport: $currentSport
        );

        $pagerfanta = new Pagerfanta(new QueryAdapter($queryBuilder));
        $pagerfanta
            ->setCurrentPage($personFilterRequest->getPage())
            ->setMaxPerPage($personFilterRequest->getLimit());

        return $this->render('person/index.html.twig', [
            'persons' => $pagerfanta,
            'genders' => Gender::cases(),
            'countries' => $countryRepository->findActiveSortedBy('name', 'ASC'),
        ]);
    }

    #[Route('/new', name: 'person_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        CurrentSportProvider $currentSportProvider
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $person = new Person();
        $person->setCreatedBy($user);
        $person->setModifiedBy($user);
        $currentSport = $currentSportProvider->getSport();

        if (!$currentSport) {
            $this->addFlash('danger', 'Sport not selected');
            return $this->redirectToRoute('person_index');
        }

        $form = $this->createForm(PersonType::class, $person, [
            'current_sport' => $currentSport,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $person->setSport($currentSport);
            $em->persist($person);
            $em->flush();

            return new JsonResponse(['success' => true, 'message' => 'Person created.']);
        }

        return $this->render('person/_modal.html.twig', [
            'form' => $form->createView(),
            'person' => $person,
        ]);
    }

    #[Route('/{id}/edit', name: 'person_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Person $person, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $person->setModifiedBy($user);
        $form = $this->createForm(PersonType::class, $person);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return new JsonResponse(['success' => true, 'message' => 'Person updated.']);
        }

        return $this->render('person/_modal.html.twig', [
            'form' => $form->createView(),
            'person' => $person,
        ]);
    }

    #[Route('/{id}', name: 'person_delete', methods: ['POST'])]
    public function delete(Request $request, Person $person, EntityManagerInterface $em): Response
    {
        if (
            $response = $this->validateCsrfToken(
                'delete' . $person->getId(),
                (string) $request->request->get('_token')
            )
        ) {
            return $response;
        }

        /** @var User $user */
        $user = $this->getUser();
        $person->setModifiedBy($user);
        $person->setIsActive(false);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Person deleted.']);
    }

    #[Route('/person-by-current-team/{teamId}/{teamFilter}', name: 'person_by_current_team', methods: ['GET'])]
    public function findByTeam(
        PersonRepository $personRepository,
        CurrentSportProvider $currentSportProvider,
        int $teamId,
        string $teamFilter
    ): JsonResponse {
        $filterEnum = TeamFilter::tryFrom($teamFilter);

        if ($filterEnum === null) {
            throw new InvalidArgumentException('Invalid team filter');
        }
        $persons = $personRepository->findActiveSortedBy(
            sport: $currentSportProvider->getSport(),
            currentTeamId: $teamId,
            teamFilter: $filterEnum
        );

        $result = [];
        foreach ($persons as $person) {
            $result[] = [
                'id' => $person->getId(),
                'firstName' => $person->getFirstName(),
                'lastName' => $person->getLastName(),
                'currentTeamName' => $person->getCurrentTeam()?->getName(),
            ];
        }

        return $this->json($result);
    }
}
