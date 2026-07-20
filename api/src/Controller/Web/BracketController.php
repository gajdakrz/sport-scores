<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Controller\BaseController;
use App\Entity\Competition;
use App\Entity\Season;
use App\Entity\Team;
use App\Service\BracketBuilder;
use App\Service\CurrentSportProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/brackets')]

final class BracketController extends BaseController
{
    /**
     * @throws ExceptionInterface
     */
    #[Route(
        '/competitions/{competition}/seasons/{season}/teams/{team}',
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
        SerializerInterface $serializer
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

        // Bracket data (team/event names) is user-editable content rendered raw into a
        // <script> block; hex-encode <, >, &, ' and " so it can't break out of the tag (XSS).
        $jsSafeJsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

        $bracketJson = $serializer->serialize(
            $bracketDto,
            'json',
            [
                'datetime_format' => 'Y-m-d',
                JsonEncode::OPTIONS => $jsSafeJsonFlags,
            ]
        );

        $highlightTeamJson = $team !== null
            ? json_encode($team->getName(), $jsSafeJsonFlags | JSON_THROW_ON_ERROR)
            : 'null';

        return $this->render('bracket/show.html.twig', [
            'bracketJson' => $bracketJson,
            'highlightTeamJson' => $highlightTeamJson,
            'competition' => $competition,
            'season' => $season,
            'highlightTeam' => $team
        ]);
    }
}
