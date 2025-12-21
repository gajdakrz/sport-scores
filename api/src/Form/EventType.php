<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Competition;
use App\Entity\Event;
use App\Entity\Sport;
use App\Repository\CompetitionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ?Event $event */
        $event = $builder->getData();
        /** @var ?Sport $sport */
        $sport = $options['current_sport'] ?? $event?->getCompetition()?->getSport();

        $builder
            ->add('sport', TextType::class, [
                'mapped' => false,
                'disabled' => true,
                'data' => $sport?->getName(),
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
                            'ASC',
                            $sport,
                        ),
            ])
            ->add('name', TextType::class, [
                'label' => 'Event name',
            ])
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
                /** @var ?Event $data */
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
            'current_sport' => null,
        ]);

        $resolver->setAllowedTypes('current_sport', [Sport::class, 'null']);
    }
}
