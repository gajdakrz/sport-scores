<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Country;
use App\Entity\Sport;
use App\Entity\Team;
use App\Enum\GenderCategory;
use App\Enum\TeamType as TeamTypeEnum;
use App\Repository\CountryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

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
            ->add('name', TextType::class, [
                'label' => 'Team name',
            ])
            ->add('gender', EnumType::class, [
                'label' => 'Gender',
                'class' => GenderCategory::class,
                'choice_label' => fn(GenderCategory $enum) => $enum->label(),
                'placeholder' => 'Select gender',
                'constraints' => [new NotNull()],
                // keep the entity setter strict: an empty choice is reported by NotNull instead of passing null
                'setter' => function (Team $team, ?GenderCategory $gender): void {
                    if ($gender !== null) {
                        $team->setGender($gender);
                    }
                },
            ])
            ->add('country', EntityType::class, self::countryFieldOptions([
                'label' => 'Country',
                'placeholder' => 'Select country',
            ]))
            ->add('teamType', EnumType::class, [
                'label' => 'Team type',
                'class' => TeamTypeEnum::class,
                'choice_label' => fn(TeamTypeEnum $enum) => $enum->label(),
                'placeholder' => 'Select type',
                // the frontend disables "competitionCountry" for this type (TeamCompetitionCountryAllowed)
                'attr' => ['data-national-value' => TeamTypeEnum::NATIONAL->value],
            ])
            ->add('competitionCountry', EntityType::class, self::countryFieldOptions([
                'label' => 'Competition country',
                'placeholder' => 'Not applicable',
                'help' => 'Country whose national competitions the team plays in. '
                    . 'Leave empty for teams that play only international competitions. '
                    . 'Not available for national teams.',
                'required' => false,
            ]))
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

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private static function countryFieldOptions(array $options): array
    {
        return $options + [
            'class' => Country::class,
            'choice_label' => 'name',
            'query_builder' =>
                fn(CountryRepository $countryRepository) => $countryRepository->createActiveQueryBuilder(
                    'name',
                    'ASC'
                ),
        ];
    }
}
