<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Competition;
use App\Entity\Event;
use App\Entity\Game;
use App\Entity\Sport;
use App\Repository\CompetitionRepository;
use App\Repository\EventRepository;
use App\Repository\SportRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class GameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sport', EntityType::class, [
                'class' => Sport::class,
                'choice_label' => 'name',
                'mapped' => false,
                'placeholder' => 'Select sport',
                'query_builder' =>
                    fn(SportRepository $sportRepository) =>$sportRepository->createActiveQueryBuilder(
                        'name',
                        'ASC'
                    ),
            ])
            ->add('competition', EntityType::class, [
                'class' => Competition::class,
                'choice_label' => 'name',
                'mapped' => false,
                'placeholder' => 'Select competition',
                'query_builder' =>
                    fn(CompetitionRepository $competitionRepository) =>$competitionRepository->createActiveQueryBuilder(
                        'name',
                        'ASC'
                    ),
                'required' => false,
            ])
            ->add('event', EntityType::class, [
                'class' => Event::class,
                'choice_label' => 'name',
                'placeholder' => 'Select event',
                'query_builder' =>
                    fn(EventRepository $eventRepository) =>$eventRepository->createActiveQueryBuilder(
                        'name',
                        'ASC'
                    ),
                'required' => true,
            ])
            ->add('name', TextType::class, [
                'label' => 'Game name',
                'required' => false,
                'empty_data' => null,
            ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            /** @var Game|null $game */
            $game = $event->getData();
            $form = $event->getForm();

            if (!$game) {
                return;
            }

            $competition = $game->getEvent()?->getCompetition();
            $sport = $game->getEvent()?->getCompetition()?->getSport();

            if ($sport) {
                $form->get('sport')->setData($sport);
            }

            if ($competition) {
                $form->get('competition')->setData($competition);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Game::class,
        ]);
    }
}
