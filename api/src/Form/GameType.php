<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Competition;
use App\Entity\Event;
use App\Entity\Game;
use App\Entity\Season;
use App\Entity\Sport;
use App\Repository\CompetitionRepository;
use App\Repository\EventRepository;
use App\Repository\SeasonRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class GameType extends AbstractType
{
    /**
     * @param array{
     *     current_sport: ?Sport
     * } $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ?Game $game */
        $game = $builder->getData();
        /** @var ?Sport $sport */
        $sport = $options['current_sport'] ?? $game?->getEvent()?->getCompetition()?->getSport();

        $builder
            ->add('sport', TextType::class, [
                'mapped' => false,
                'disabled' => true,
                'data' => $sport?->getName(),
            ])
            ->add('season', EntityType::class, [
                'class' => Season::class,
                'choice_label' => 'mergedStartEndYear',
                'placeholder' => 'Select season',
                'query_builder' =>
                    fn(SeasonRepository $seasonRepository) => $seasonRepository->createActiveQueryBuilder('endYear'),
            ])
            ->add('competition', EntityType::class, [
                'class' => Competition::class,
                'choice_label' => 'name',
                'mapped' => false,
                'placeholder' => 'Select competition',
                'query_builder' =>
                    fn(
                        CompetitionRepository $competitionRepository
                    ) => $competitionRepository->createActiveQueryBuilder(
                        'name',
                        'ASC',
                        $sport,
                    ),
                'required' => false,
            ])
            ->add('event', EntityType::class, [
                'class' => Event::class,
                'choice_label' => 'name',
                'placeholder' => 'Select event',
                'choices' => $game && $game->getId()
                    ? [$game->getEvent()]
                    : [],
                'required' => true,
            ])
            ->add('date', DateType::class, [
                'label' => 'Game date',
                'widget' => 'single_text',
                'required' => true,
                'attr' => [
                    'class' => 'form-control flatpickr',
                    'data-default-date' => 'today',
                ],
            ])
            ->add('gameResults', CollectionType::class, [
                'entry_type' => GameResultType::class,
                'entry_options' => [
                    'label' => false,
                    'include_game' => false,
                    'current_sport' => $sport,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
            ])
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
                /** @var ?Game $data */
                $data = $event->getData();
                $form = $event->getForm();

                if (!$data) {
                    return;
                }

                $competition = $data->getEvent()?->getCompetition();
                if ($competition) {
                    $form->get('competition')->setData($competition);
                }
            })
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                /** @var array<string, mixed> $data */
                $data = $event->getData();
                $form = $event->getForm();

                $competitionId = $data['competition'] ?? null;

                if ($competitionId) {
                    $form->add('event', EntityType::class, [
                        'class' => Event::class,
                        'choice_label' => 'name',
                        'placeholder' => 'Select event',
                        'required' => true,
                        'query_builder' => function (EventRepository $eventRepository) use ($competitionId) {
                            return $eventRepository->createQueryBuilder('e')
                                ->where('e.competition = :competitionId')
                                ->setParameter('competitionId', $competitionId);
                        },
                    ]);
                }
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Game::class,
            'current_sport' => null,
        ]);

        $resolver->setAllowedTypes('current_sport', [Sport::class, 'null']);
    }
}
