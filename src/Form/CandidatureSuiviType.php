<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class CandidatureSuiviType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reference', TextType::class, [
                'label' => 'Référence de candidature',
                'attr' => [
                    'placeholder' => 'Ex: CAN12345',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer une référence de candidature'
                    ]),
                    new Regex([
                        'pattern' => '/^CAN\d{5}$/',
                        'message' => 'La référence doit être au format CAN suivi de 5 chiffres (ex: CAN12345)'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'attr' => ['novalidate' => 'novalidate'] // Désactive la validation HTML5 pour utiliser uniquement la validation Symfony
        ]);
    }
}
