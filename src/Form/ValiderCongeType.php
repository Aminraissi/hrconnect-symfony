<?php

namespace App\Form;

use App\Entity\DemandeConge;
use App\Entity\ValiderConge;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\NotBlank;

class ValiderCongeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'En attente' => 'EN_ATTENTE',
                    'Acceptée' => 'ACCEPTEE',
                    'Refusée' => 'REFUSEE',
                ],
                'label' => 'Statut de validation',
                'attr' => ['class' => 'form-select form-control-lg'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un statut']),
                ],
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Ajoutez un commentaire si nécessaire',
                ],
            ])
            ->add('demandeConge', EntityType::class, [
                'class' => DemandeConge::class,
                'choice_label' => function (DemandeConge $demandeConge) {
                    return 'Demande #' . $demandeConge->getId() . ' - ' . $demandeConge->getTypeConge();
                },
                'label' => 'Demande de congé associée',
                'placeholder' => 'Sélectionner une demande de congé',
                'attr' => ['class' => 'form-select form-control-lg'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner une demande de congé']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ValiderConge::class,
        ]);
    }
}