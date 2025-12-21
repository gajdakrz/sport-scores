<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Country;
use App\Entity\Sport;
use App\Entity\Team;
use App\Enum\TeamType as TeamTypeEnum;
use App\Repository\CountryRepository;
use App\Repository\SportRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class TeamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ?Team $team */
        $team = $builder->getData();
        /** @var ?Sport $sport */
        $sport = $options['current_sport'] ?? $team?->getSport();

        $builder
            ->add('sport', TextType::class, [
                'mapped' => false,
                'disabled' => true,
                'data' => $sport?->getName(),
            ])
            ->add('teamType', EnumType::class, [
                'label' => 'Team type',
                'class' => TeamTypeEnum::class,
                'choice_label' => fn(TeamTypeEnum $enum) => $enum->label(),
                'placeholder' => 'Select type',
            ])
            ->add('country', EntityType::class, [
                'label' => 'Country',
                'class' => Country::class,
                'choice_label' => 'name',
                'placeholder' => 'Select country',
                'query_builder' =>
                    fn(CountryRepository $countryRepository) =>$countryRepository->createActiveQueryBuilder(
                        'name',
                        'ASC'
                    ),
            ])
            ->add('name', TextType::class, [
                'label' => 'Team name',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Team::class,
            'current_sport' => null,
        ]);

        $resolver->setAllowedTypes('current_sport', [Sport::class, 'null']);
    }
}
