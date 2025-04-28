<?php

namespace App\Form;

use App\Entity\TicketReclamation;
use App\Entity\Reclamation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reclamation', EntityType::class, [
                'class' => Reclamation::class,
                'choice_label' => function (Reclamation $rec) {
                    return 'ID: ' . $rec->getId() . ' - ' . $rec->getEmployeeName();
                },
                'label' => 'Réclamation liée',
                'placeholder' => 'Sélectionnez une réclamation',
                'attr' => ['class' => 'form-control']
            ])
            ->add('hrStaffName', TextType::class, [
                'label' => 'Nom du RH',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('responseMessage', TextareaType::class, [
                'label' => 'Message de réponse',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'maxlength' => 500,
                    'placeholder' => 'Décrivez brièvement votre réponse'
                ]
            ])
            ->add('actionTaken', TextareaType::class, [
                'label' => 'Action prise',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'maxlength' => 500,
                    'placeholder' => 'Indiquez les mesures prises'
                ]
            ])
            ->add('dateOfResponse', DateTimeType::class, [
                'label' => 'Date de réponse',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('resolutionStatus', ChoiceType::class, [
                'label' => 'Statut de résolution',
                'choices' => [
                    'Résolu' => 'Resolved',
                    'Transféré' => 'Escalated',
                    'Fermé' => 'Closed',
                ],
                'placeholder' => 'Choisissez un statut',
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TicketReclamation::class,
        ]);
    }
}
