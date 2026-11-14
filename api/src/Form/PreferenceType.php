<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\Request\PreferenceRequest;
use App\Entity\Competition;
use App\Entity\Sport;
use App\Entity\SportScopedInterface;
use App\Entity\Team;
use App\Repository\CompetitionRepository;
use App\Repository\SportRepository;
use App\Repository\TeamRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PreferenceType extends AbstractType
{
    private const string NONE = 'None';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('favouriteSport', EntityType::class, [
                'class' => Sport::class,
                'choice_label' => 'name',
                'label' => 'Favourite sport',
                'placeholder' => self::NONE,
                'required' => false,
                'query_builder' => fn(SportRepository $repository) => $repository
                    ->createActiveQueryBuilder('name', 'ASC'),
            ])
            ->add('favouriteCompetition', EntityType::class, [
                'class' => Competition::class,
                'choice_label' => 'name',
                'label' => 'Favourite competition',
                'placeholder' => self::NONE,
                'required' => false,
                'query_builder' => fn(CompetitionRepository $repository) => $repository
                    ->createActiveQueryBuilder('name', 'ASC'),
                'choice_attr' => $this->sportIdAttr(...),
            ])
            ->add('favouriteTeam', EntityType::class, [
                'class' => Team::class,
                'choice_label' => 'name',
                'label' => 'Favourite team',
                'placeholder' => self::NONE,
                'required' => false,
                'query_builder' => fn(TeamRepository $repository) => $repository
                    ->createActiveQueryBuilder('name', 'ASC'),
                'choice_attr' => $this->sportIdAttr(...),
            ])
            ->add('currentSeasonByDefault', CheckboxType::class, [
                'label' => 'Games: "Current season" filter on by default',
                'required' => false,
            ])
        ;
    }

    /**
     * Lets the modal offer only the options of the selected sport.
     *
     * @return array<string, string>
     */
    private function sportIdAttr(SportScopedInterface $choice): array
    {
        return ['data-sport-id' => (string) $choice->getSport()?->getId()];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PreferenceRequest::class,
        ]);
    }
}
