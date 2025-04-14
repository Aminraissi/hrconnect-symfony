<?php

namespace App\Form;

use App\Entity\OffreEmploi;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OffreEmploiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de l\'offre',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Développeur Symfony'
                ]
            ])
            ->add('typeContrat', ChoiceType::class, [
                'label' => 'Type de contrat',
                'choices' => [
                    'CDI' => 'CDI',
                    'CDD' => 'CDD',
                    'Stage' => 'Stage',
                    'Alternance' => 'Alternance',
                    'Freelance' => 'Freelance'
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('localisation', TextType::class, [
                'label' => 'Localisation',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Paris'
                ]
            ])
            ->add('salaire', TextType::class, [
                'label' => 'Salaire',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 45-55K€'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Description détaillée de l\'offre'
                ]
            ])
            ->add('profilRecherche', TextareaType::class, [
                'label' => 'Profil recherché',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Profil et compétences recherchés'
                ]
            ])
            ->add('avantages', TextareaType::class, [
                'label' => 'Avantages',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Avantages proposés'
                ]
            ])
            ->add('isActive', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Active' => true,
                    'Inactive' => false
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OffreEmploi::class,
            'attr' => ['novalidate' => 'novalidate'] // Désactive la validation HTML5 pour utiliser uniquement la validation Symfony
        ]);
    }
}