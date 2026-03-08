<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\MemberPosition;
use App\Entity\Person;
use App\Entity\Season;
use App\Entity\Sport;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Repository\MemberPositionRepository;
use App\Repository\PersonRepository;
use App\Repository\SeasonRepository;
use App\Repository\TeamRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

final class TeamMemberType extends AbstractType
{
    public function __construct(
        private readonly RouterInterface $router
    ) {
    }

    /**
     * @param array{
     *     current_sport: ?Sport
     * } $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ?TeamMember $teamMember */
        $teamMember = $builder->getData();
        /** @var ?Sport $sport */
        $sport = $options['current_sport'] ?? $teamMember?->getTeam()?->getSport();
        $isEdit = $teamMember && $teamMember->getId();

        $builder
            ->add('team', EntityType::class, [
                'class' => Team::class,
                'choice_label' => 'name',
                'placeholder' => 'Select team',
                'query_builder' =>
                    fn(
                        TeamRepository $teamRepository
                    ) => $teamRepository->createActiveQueryBuilder(
                        'name',
                        'ASC',
                        $sport,
                    ),
                'required' => true,
                'attr' => [
                    'data-url' => $this->router->generate('person_by_current_team', [
                        'teamId' => 'TEAM_ID',
                        'teamFilter' => 'TEAM_FILTER'
                    ]),
                ],
            ])
            ->add('startSeason', EntityType::class, [
                'class' => Season::class,
                'choice_label' => 'mergedStartEndYear',
                'placeholder' => 'Select start season',
                'query_builder' =>
                    fn(SeasonRepository $seasonRepository) => $seasonRepository->createActiveQueryBuilder('endYear'),
            ])
            ->add('isCurrentMember', CheckboxType::class, [
                'label' => 'Is current',
                'required' => false,
            ])
            ->add('person', EntityType::class, [
                'class' => Person::class,
                'choice_label' => function (Person $person): string {
                    return sprintf(
                        '%s %s (obecnie: %s)',
                        $person->getLastName(),
                        $person->getFirstName(),
                        $person->getCurrentTeam()?->getName()
                    );
                },
                'placeholder' => 'Select person',
                'choices' => $teamMember && $teamMember->getId()
                    ? [$teamMember->getPerson()]
                    : [],
                'required' => true,
            ])
            ->add('memberPosition', EntityType::class, [
                'class' => MemberPosition::class,
                'choice_label' => 'name',
                'placeholder' => 'Select member position',
                'query_builder' =>
                    fn(
                        MemberPositionRepository $memberPositionRepository
                    ) => $memberPositionRepository->createActiveQueryBuilder(
                        'name',
                        'ASC',
                        $sport
                    ),
                'required' => true,
            ])
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($isEdit, $teamMember) {
                /** @var array<string, mixed> $data */
                $data = $event->getData();
                $form = $event->getForm();

                $teamId = $data['team'] ?? null;
                $isCurrentMember = isset($data['isCurrentMember']) && $data['isCurrentMember'];
                $originalPersonId = $teamMember?->getPerson()?->getId();

                if ($teamId) {
                    $form->add('person', EntityType::class, [
                        'class' => Person::class,
                        'choice_label' => function (Person $person): string {
                            return sprintf(
                                '%s %s (obecnie: %s)',
                                $person->getLastName(),
                                $person->getFirstName(),
                                $person->getCurrentTeam()?->getName()
                            );
                        },
                        'placeholder' => 'Select person',
                        'required' => true,
                        'query_builder' => function (PersonRepository $personRepository) use (
                            $teamId,
                            $isEdit,
                            $isCurrentMember,
                            $originalPersonId
                        ) {
                            $qb = $personRepository->createQueryBuilder('p');

                            if ($isEdit) {
                                $qb->where('p.currentTeam = :teamId')
                                    ->orWhere('p.currentTeam IS NULL');

                                if ($originalPersonId !== null) {
                                    $qb->orWhere('p.id = :originalPersonId')
                                        ->setParameter('originalPersonId', $originalPersonId);
                                }

                                return $qb->setParameter('teamId', $teamId);
                            }

                            if ($isCurrentMember) {
                                return $qb->where('p.currentTeam != :teamId')
                                    ->orWhere('p.currentTeam IS NULL')
                                    ->setParameter('teamId', $teamId);
                            }

                            return $qb;
                        },
                    ]);
                }
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TeamMember::class,
            'current_sport' => null,
        ]);

        $resolver->setAllowedTypes('current_sport', [Sport::class, 'null']);
    }
}
