<?php

namespace App\Form;

use App\Entity\ParticipationSeminaire;
use App\Entity\Seminaire;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipationSeminaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Inscrit' => 'Inscrit',
                    'Présent' => 'présent',
                    'Absent' => 'absent',
                    'En attente' => 'en attente',
                ],
                'placeholder' => 'Choisir un statut',
                'label' => 'Statut',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateInscription', DateType::class, [
                'widget' => 'single_text',
                'data' => new \DateTime(), // Default to current date
                'label' => 'Date d\'inscription',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('evaluation', null, [
                'label' => 'Évaluation',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('certificat', null, [
                'label' => 'Certificat',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('idEmploye', null, [
                'label' => 'ID Employé',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('seminaire', EntityType::class, [
                'class' => Seminaire::class,
                'choice_label' => 'nomSeminaire',
                'placeholder' => 'Sélectionner un séminaire',
                'label' => 'Séminaire',
                'disabled' => $options['disabled_seminaire'], // Disable if pre-selected
                'attr' => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ParticipationSeminaire::class,
            'disabled_seminaire' => false, // Default to false
        ]);

        $resolver->setAllowedTypes('disabled_seminaire', 'bool');
    }
}