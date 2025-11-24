<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Competition;
use App\Entity\Event;
use App\Entity\Sport;
use App\Repository\CompetitionRepository;
use App\Repository\SportRepository;
use DateTimeImmutable;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EventType extends AbstractType
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
                    fn(SportRepository $sportRepository) => $sportRepository->createActiveQueryBuilder(
                        'name',
                        'ASC'
                    ),
            ])
            ->add('competition', EntityType::class, [
                'label' => 'Competition',
                'class' => Competition::class,
                'choice_label' => 'name',
                'placeholder' => 'Select competition',
                'query_builder' =>
                    fn(CompetitionRepository $competitionRepository) => $competitionRepository
                        ->createActiveQueryBuilder(
                            'name',
                            'ASC'
                        ),
            ])
            ->add('name', TextType::class, [
                'label' => 'Event name',
            ])
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
                /** @var Event|null $data */
                $data = $event->getData();
                $form = $event->getForm();

                if (!$data) {
                    return;
                }
                $sport = $data->getCompetition()?->getSport();

                if ($sport) {
                    $form->get('sport')->setData($sport);
                }
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
