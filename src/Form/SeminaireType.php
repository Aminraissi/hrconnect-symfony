<?php

namespace App\Form;

use App\Entity\Seminaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SeminaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $today = (new \DateTime())->format('Y-m-d');
        $nextWeek = (new \DateTime())->modify('+7 days')->format('Y-m-d');

        $builder
            ->add('nomSeminaire')
            ->add('description')
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'empty_data' => $today,
                'data' => new \DateTime(), // Valeur par défaut à l'affichage
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'empty_data' => $nextWeek,
                'data' => (new \DateTime())->modify('+7 days'),
            ])
            ->add('lieu')
            ->add('formateur')
            ->add('cout')
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Séminaire' => 'Séminaire',
                    'Conférence' => 'Conférence',
                    'Atelier' => 'Atelier',
                    'Formation' => 'Formation',
                ],
                'placeholder' => 'Choisir un type...',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Seminaire::class,
        ]);
    }
}