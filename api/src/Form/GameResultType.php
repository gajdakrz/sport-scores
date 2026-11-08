<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Game;
use App\Entity\GameResult;
use App\Entity\Sport;
use App\Entity\Team;
use App\Repository\GameRepository;
use App\Repository\TeamRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

final class GameResultType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ?GameResult $gameResult */
        $gameResult = $builder->getData();
        /** @var ?Sport $sport */
        $sport = $options['current_sport'] ?? $gameResult?->getGame()?->getEvent()?->getCompetition()?->getSport();

        if ($options['include_game']) {
            $builder->add('game', EntityType::class, [
                'label' => 'Game',
                'class' => Game::class,
                'choice_label' => fn (Game $game) => $game->getDate()->format('Y-m-d'),
                'choice_attr' => fn(Game $game): array => [
                    'data-gender' => $game->getEvent()?->getCompetition()?->getGender()->value ?? '',
                    // empty = international competition (every country allowed)
                    'data-country' => (string) $game->getEvent()?->getCompetition()?->getCountry()?->getId(),
                ],
                'placeholder' => 'Select game date',
                'query_builder' =>
                    fn(GameRepository $gameRepository) => $gameRepository->createActiveQueryBuilder(
                        'date',
                        'ASC',
                        $sport
                    ),
            ]);
        }

        $builder
            ->add('team', EntityType::class, [
                'label' => 'Team',
                'class' => Team::class,
                'choice_label' => 'name',
                'choice_attr' => fn(Team $team): array => [
                    'data-gender' => $team->getGender()->value,
                    // empty = the team plays only in international competitions
                    'data-country' => (string) $team->getCompetitionCountry()?->getId(),
                ],
                'placeholder' => 'Select team',
                // this form records team results only, so a team is required
                // (the database check chk_team_or_person would otherwise fail with a 500)
                'constraints' => [new NotNull(message: 'Select a team for every result.')],
                'query_builder' =>
                    fn(TeamRepository $teamRepository) => $teamRepository->createActiveQueryBuilder(
                        'name',
                        'ASC',
                        $sport
                    ),
            ])
            ->add('matchScore', IntegerType::class, [
                'label' => 'Match score',
                'required' => false,
                'empty_data' => null,
                'attr' => [
                    'min' => 0,
                ],
            ])
            ->add('rankingScore', IntegerType::class, [
                'label' => 'Ranking score',
                'required' => false,
                'empty_data' => null,
                'attr' => [
                    'min' => 1,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GameResult::class,
            'include_game' => true,
            'current_sport' => null,
        ]);

        $resolver->setAllowedTypes('include_game', 'bool');
        $resolver->setAllowedTypes('current_sport', [Sport::class, 'null']);
    }
}
