<?php

namespace App\Form;

use App\Entity\ValiderConge;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
            ->add('demandeInfo', TextType::class, [
                'mapped' => false,
                'data' => $options['demande_info'] ?? '',
                'label' => 'Demande de congé associée',
                'disabled' => true,
                'attr' => ['class' => 'form-control form-control-lg'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ValiderConge::class,
            'demande_info' => null,
        ]);
    }
}
