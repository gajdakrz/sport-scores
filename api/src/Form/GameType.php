<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Event;
use App\Entity\Game;
use App\Repository\EventRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class GameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Game name',
                'required' => false,
                'empty_data' => null,
            ])
            ->add('event', EntityType::class, [
                'label' => 'Event',
                'class' => Event::class,
                'choice_label' => 'name',
                'placeholder' => 'Select event',
                'query_builder' =>
                    fn(EventRepository $eventRepository) =>$eventRepository->createActiveQueryBuilder(
                        'name',
                        'ASC'
                    ),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Game::class,
        ]);
    }
}
