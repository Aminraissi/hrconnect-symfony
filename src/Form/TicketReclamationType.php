<?php

namespace App\Form;

use App\Entity\TicketReclamation;
use App\Entity\Reclamation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
                'choice_label' => 'id', // you can customize with something more readable like employeeName
                'label' => 'Réclamation liée',
                'attr' => ['class' => 'form-control']
            ])
            ->add('hrStaffName', TextType::class, [
                'label' => 'Nom du RH',
                'attr' => ['class' => 'form-control']
            ])
            ->add('responseMessage', TextareaType::class, [
                'label' => 'Message de réponse',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
            ->add('actionTaken', TextareaType::class, [
                'label' => 'Action prise',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
            ->add('resolutionStatus', ChoiceType::class, [
                'label' => 'Statut de résolution',
                'choices' => [
                    'Résolu' => 'Resolved',
                    'Transféré' => 'Escalated',
                    'Fermé' => 'Closed',
                ],
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
