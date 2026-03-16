<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Competition;
use App\Entity\Event;
use App\Entity\Sport;
use App\Repository\CompetitionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

final class EventType extends AbstractType
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly CompetitionRepository $competitionRepository
    ) {
    }

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
                'query_builder' => $this->competitionRepository->createActiveQueryBuilder(
                            'name',
                            'ASC',
                            $sport,
                ),
                'choice_attr' => function (Competition $competition) {
                    return [
                        'data-is-bracket' => $competition->isBracket() ? 'true' : 'false',
                    ];
                },
                'attr' => [
                    'data-url' => $this->router->generate('event_by_competition', [
                        'competitionId' => 'COMPETITION_ID'
                    ]),
                ],
            ])
            ->add('orderIndex', IntegerType::class, [
                'label' => 'Order index',
                'attr' => [
                    'min' => 1,
                ],
                'required' => false,
                'empty_data' => null,
                'disabled' => true,
            ])
            ->add('name', TextType::class, [
                'label' => 'Event name',
            ])
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
                /** @var ?Event $data */
                $data = $event->getData();
                $form = $event->getForm();

                if ($data === null) {
                    return;
                }

                $competition = $data->getCompetition();

                if ($competition === null) {
                    return;
                }

                $sport = $competition->getSport();

                if ($sport !== null) {
                    $form->get('sport')->setData($sport);
                }

                $form->add('orderIndex', IntegerType::class, [
                    'label' => 'Order index',
                    'attr' => [
                        'min' => 1,
                    ],
                    'required' => false,
                    'empty_data' => null,
                    'disabled' => $competition->isBracket() === false,
                ]);
            })
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $formEvent) {
                $data = $formEvent->getData();
                $form = $formEvent->getForm();

                if (!is_array($data)) {
                    return;
                }

                if ($form->get('orderIndex')->getConfig()->getOption('disabled') === false) {
                    return;
                }

                $competitionId = $data['competition'] ?? null;
                if ($competitionId === null) {
                    return;
                }

                $competition = $this->competitionRepository->find($competitionId);
                if ($competition?->isBracket()) {
                    $form->add('orderIndex', IntegerType::class, [
                        'label' => 'Order index',
                        'attr' => ['min' => 1],
                        'required' => false,
                        'empty_data' => null,
                        'disabled' => false,
                    ]);
                }
            })
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                /** @var ?Event $data */
                $data = $event->getData();
                $form = $event->getForm();

                if ($data === null) {
                    return;
                }

                $competition = $data->getCompetition();

                if ($competition === null) {
                    return;
                }

                $orderIndex = is_int($raw = $form->get('orderIndex')->getData()) ? $raw : null;

                if ($competition->isBracket() === false && $orderIndex !== null) {
                    $form->get('orderIndex')->addError(
                        new FormError('Order index is not required for non bracket competition: ' . $competition->getName())
                    );

                    return;
                }

                $this->validOrderIndex($event, $competition, $orderIndex);
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

    private function validOrderIndex(FormEvent $event, Competition $competition, ?int $orderIndex): void
    {
        /** @var Event $data */
        $data = $event->getData();
        $form = $event->getForm();
        $errorMessage = '';

        switch (true) {
            case $orderIndex === null:
                $errorMessage = 'Order index is required for bracket competitions';
                break;

            case $orderIndex < 1:
                $errorMessage = 'Order index must be at least 1';
                break;

            default:
                $events = $competition->getEvents();

                foreach ($events as $event) {
                    if ($event->getOrderIndex() === $data->getOrderIndex() && $event->getId() !== $data->getId()) {
                        $errorMessage =
                            'Order index must be unique for events in bracket competition: ' . $competition->getName();
                        break;
                    }
                }
                break;
        }

        if ($errorMessage !== '') {
            $form->get('orderIndex')->addError(new FormError($errorMessage));
        }
    }
}
