<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Competition;
use App\Entity\Season;
use App\Entity\Team;
use App\Service\BracketBuilder;
use App\Service\CurrentSportProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/brackets')]

final class BracketController extends AbstractController
{
    #[Route(
        '/competitions/{competition}/seasons/{season}/teams/{team}/build',
        name: 'build_bracket',
        defaults: ['team' => null],
        methods: ['GET']
    )]
    public function buildBracket(
        Competition $competition,
        Season $season,
        ?Team $team,
        BracketBuilder $builder,
        CurrentSportProvider $currentSportProvider,
    ): Response {
        $currentSport = $currentSportProvider->getSport();

        if ($team !== null && $team->getSport() !== $currentSport) {
            $this->addFlash('danger', 'Missing team for selected sport');
            return $this->redirectToRoute('team_index');
        }

        if ($competition->getSport() !== $currentSport) {
            $this->addFlash('danger', 'Missing competition for selected sport');
            return $this->redirectToRoute('competition_index');
        }

        $bracketDto = $builder->build($competition, $season);

        return $this->render('bracket/show.html.twig', [
            'bracket' => $bracketDto,
            'competition' => $competition,
            'season' => $season,
            'highlightTeam' => $team
        ]);
    }
}
