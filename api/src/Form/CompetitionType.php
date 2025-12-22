<?php

namespace App\Form;

use App\Entity\Competition;
use App\Entity\Sport;
use App\Enum\Gender;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
                'class' => Gender::class,
                'choice_label' => fn(Gender $enum) => $enum->label(),
                'placeholder' => 'Select type',
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
