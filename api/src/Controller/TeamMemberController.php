<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Filter\TeamMemberFilterDto;
use App\Entity\TeamMember;
use App\Entity\User;
use App\Exception\CustomBadRequestException;
use App\Form\TeamMemberType;
use App\Repository\SeasonRepository;
use App\Repository\TeamMemberRepository;
use App\Repository\TeamRepository;
use App\Service\CurrentSportProvider;
use App\Service\TeamMemberService;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/team-members')]
final class TeamMemberController extends AbstractController
{
    public function __construct(
        private readonly TeamMemberService $teamMemberService
    ) {
    }

    #[Route('', name: 'team_member_index', methods: ['GET'])]
    public function index(
        #[MapQueryString] TeamMemberFilterDto $teamMemberFilterRequest,
        TeamMemberRepository $teamMemberRepository,
        TeamRepository $teamRepository,
        SeasonRepository $seasonRepository,
        CurrentSportProvider $currentSportProvider,
    ): Response {
        $currentSport = $currentSportProvider->getSport();
        $queryBuilder = $teamMemberRepository->createActiveByFilterBuilder(
            filter: $teamMemberFilterRequest,
            sport: $currentSportProvider->getSport()
        );

        $pagerfanta = new Pagerfanta(new QueryAdapter($queryBuilder));
        $pagerfanta
            ->setCurrentPage($teamMemberFilterRequest->getPage())
            ->setMaxPerPage($teamMemberFilterRequest->getLimit());

        return $this->render('team_member/index.html.twig', [
            'teamMembers' => $pagerfanta,
            'teams' => $teamRepository->findActiveSortedBy('name', 'ASC', $currentSport),
            'seasons' => $seasonRepository->findActiveSortedBy('startYear'),
        ]);
    }

    #[Route('/new', name: 'team_member_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        CurrentSportProvider $currentSportProvider
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $teamMember = new TeamMember();
        $teamMember->setCreatedBy($user);
        $teamMember->setModifiedBy($user);
        $currentSport = $currentSportProvider->getSport();

        if (!$currentSport) {
            $this->addFlash('danger', 'Sport not selected');
            return $this->redirectToRoute('team_member_index');
        }

        $form = $this->createForm(TeamMemberType::class, $teamMember, [
            'current_sport' => $currentSport,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->teamMemberService->saveTeamMember($teamMember);
                $this->addFlash('success', 'Team member created.');
                return $this->redirectToRoute('team_member_index');
            } catch (CustomBadRequestException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render('team_member/_modal.html.twig', [
            'form' => $form->createView(),
            'teamMember' => $teamMember,
            'initialTeam' => null,
            'initialPerson' => null,
        ]);
    }

    #[Route('/{id}/edit', name: 'team_member_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TeamMember $teamMember): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $teamMember->setModifiedBy($user);
        $form = $this->createForm(TeamMemberType::class, $teamMember);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->teamMemberService->saveTeamMember($teamMember);
                $this->addFlash('success', 'Team member updated.');

                return new JsonResponse(['success' => true]);
            } catch (CustomBadRequestException $e) {
                $errors = $e->getErrors();
                if ($errors !== []) {
                    foreach ($errors as $error) {
                        $this->addFlash('danger', $error['message']);
                    }
                }

                return new JsonResponse(['success' => true]);
            }
        }

        return $this->render('team_member/_modal.html.twig', [
            'form' => $form->createView(),
            'teamMember' => $teamMember,
            'initialTeam' => $teamMember->getTeam(),
            'initialPerson' => $teamMember->getPerson(),
        ]);
    }

    #[Route('/{id}', name: 'team_member_delete', methods: ['POST'])]
    public function delete(Request $request, TeamMember $teamMember, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('delete' . $teamMember->getId(), (string)$request->request->get('_token'))) {
            $teamMember->setModifiedBy($user);
            $teamMember->setIsActive(false);
            $em->flush();
        }
        $this->addFlash('success', 'Team member deleted.');

        return new JsonResponse(['success' => true]);
    }
}
