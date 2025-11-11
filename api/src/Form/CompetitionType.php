<?php

namespace App\Form;

use App\Entity\Competition;
use App\Entity\Sport;
use App\Enum\Gender;
use App\Repository\SportRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompetitionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Competition name',
            ])
            ->add('gender', EnumType::class, [
                'label' => 'Gender',
                'class' => Gender::class,
                'choice_label' => fn(Gender $enum) => $enum->label(),
                'placeholder' => 'Select type',
            ])
            ->add('sport', EntityType::class, [
                'class' => Sport::class,
                'choice_label' => 'name',
                'placeholder' => 'Select sport',
                'query_builder' =>
                    fn(SportRepository $sportRepository) =>$sportRepository->createActiveQueryBuilder(
                        'name',
                        'ASC'
                    ),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Competition::class,
        ]);
    }
}
