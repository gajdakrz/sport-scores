<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Game;
use App\Entity\GameResult;
use App\Entity\Team;
use App\Repository\GameRepository;
use App\Repository\TeamRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class GameResultType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['include_game']) {
            $builder->add('game', EntityType::class, [
                'label' => 'Game',
                'class' => Game::class,
                'choice_label' => 'name',
                'placeholder' => 'Select game',
                'query_builder' =>
                    fn(GameRepository $gameRepository) => $gameRepository->createActiveQueryBuilder(
                        'name',
                        'ASC'
                    ),
            ]);
        }

        $builder
            ->add('team', EntityType::class, [
                'label' => 'Team',
                'class' => Team::class,
                'choice_label' => 'name',
                'placeholder' => 'Select team',
                'query_builder' =>
                    fn(TeamRepository $teamRepository) => $teamRepository->createActiveQueryBuilder(
                        'name',
                        'ASC'
                    ),
            ])
            ->add('matchScore', IntegerType::class, [
                'label' => 'Match score',
                'attr' => [
                    'min' => 0,
                ],
            ])
            ->add('rankingScore', IntegerType::class, [
                'label' => 'Ranking score',
                'attr' => [
                    'min' => 1,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GameResult::class,
            'include_game' => true,
        ]);

        $resolver->setAllowedTypes('include_game', 'bool');
    }
}
