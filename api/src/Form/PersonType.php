<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Country;
use App\Entity\Person;
use App\Entity\Sport;
use App\Enum\Gender;
use App\Repository\CountryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ?Person $person */
        $person = $builder->getData();
        /** @var ?Sport $sport */
        $sport = $options['current_sport'] ?? $person?->getSport();

        $builder
            ->add('sport', TextType::class, [
                'mapped' => false,
                'disabled' => true,
                'data' => $sport?->getName(),
            ])
            ->add('firstName', TextType::class, [
                'label' => 'First name',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last name',
            ])
            ->add('gender', EnumType::class, [
                'label' => 'Gender',
                'class' => Gender::class,
                'choice_label' => fn(Gender $enum) => $enum->label(),
                'placeholder' => 'Select type',
            ])
            ->add('birthDate', DateType::class, [
                'label' => 'Birth date',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control flatpickr'],
            ])
            ->add('originCountry', EntityType::class, [
                'label' => 'Origin country',
                'class' => Country::class,
                'choice_label' => 'name',
                'placeholder' => 'Select origin country',
                'required' => false,
                'query_builder' =>
                    fn(CountryRepository $countryRepository) =>$countryRepository->createActiveQueryBuilder(
                        'name',
                        'ASC'
                    ),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Person::class,
            'current_sport' => null,
        ]);

        $resolver->setAllowedTypes('current_sport', [Sport::class, 'null']);
    }
}
