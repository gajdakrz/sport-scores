<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Season;
use DateMalformedStringException;
use DateTimeImmutable;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SeasonType extends AbstractType
{
    protected DateTimeImmutable $now;

    /**
     * @throws DateMalformedStringException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->now = new DateTimeImmutable();
        $maxStartYear = (int)$this->now->format('Y');
        $maxEndYear = (int)$this->now->modify('+1 year')->format('Y');
        $minYear = (int)$this->now->modify('-50 year')->format('Y');

        $builder
            ->add('startYear', ChoiceType::class, [
                'label' => 'Season start year',
                'choices' => array_combine(
                    range($maxStartYear, $minYear),
                    range($maxStartYear, $minYear)
                ),
            ])
            ->add('endYear', ChoiceType::class, [
                'label' => 'Season end year',
                'choices' => array_combine(
                    range($maxEndYear, $minYear),
                    range($maxEndYear, $minYear)
                ),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Season::class,
        ]);
    }
}
