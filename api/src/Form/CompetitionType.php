<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Competition;
use App\Entity\Country;
use App\Entity\Sport;
use App\Enum\GenderCategory;
use App\Repository\CountryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class CompetitionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ?Competition $competition */
        $competition = $builder->getData();
        /** @var ?Sport $sport */
        $sport = $options['current_sport'] ?? $competition?->getSport();

        $builder
            ->add('sport', TextType::class, [
                'mapped' => false,
                'disabled' => true,
                'data' => $sport?->getName(),
            ])
            ->add('gender', EnumType::class, [
                'label' => 'Gender',
                'class' => GenderCategory::class,
                'choice_label' => fn(GenderCategory $enum) => $enum->label(),
                'placeholder' => 'Select gender',
                'constraints' => [new NotNull()],
                // keep the entity setter strict: an empty choice is reported by NotNull instead of passing null
                'setter' => function (Competition $competition, ?GenderCategory $gender): void {
                    if ($gender !== null) {
                        $competition->setGender($gender);
                    }
                },
            ])
            ->add('country', EntityType::class, [
                'label' => 'Country',
                'class' => Country::class,
                'choice_label' => 'name',
                // no country = international competition
                'placeholder' => 'International',
                'required' => false,
                'query_builder' =>
                    fn(CountryRepository $countryRepository) => $countryRepository->createActiveQueryBuilder(
                        'name',
                        'ASC'
                    ),
            ])
            ->add('isBracket', CheckboxType::class, [
                'label' => 'Is bracket',
                'required' => false,
            ])
            ->add('name', TextType::class, [
                'label' => 'Competition name',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Competition::class,
            'current_sport' => null,
        ]);

        $resolver->setAllowedTypes('current_sport', [Sport::class, 'null']);
    }
}
