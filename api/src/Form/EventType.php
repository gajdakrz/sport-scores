<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Competition;
use App\Entity\Event;
use App\Repository\CompetitionRepository;
use DateTimeImmutable;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Event name',
            ])
            ->add('competition', EntityType::class, [
                'label' => 'Competition',
                'class' => Competition::class,
                'choice_label' => 'name',
                'placeholder' => 'Select competition',
                'query_builder' =>
                    fn(CompetitionRepository $competitionRepository) => $competitionRepository
                        ->createIsActiveQueryBuilder(
                            true,
                            'name',
                            'ASC'
                        ),
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Start date',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'html5' => true,
                'input' => 'datetime_immutable',
                'attr' => ['class' => 'form-control flatpickr'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
