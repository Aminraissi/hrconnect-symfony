<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType; // ADD THIS
use Symfony\Component\OptionsResolver\OptionsResolver;

class SignupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class)
            ->add('prenom', TextType::class)
            ->add('cin', TextType::class)
            ->add('tel', TextType::class)
            ->add('email', TextType::class)
            ->add('password', PasswordType::class)

            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'RH' => 'ROLE_RH',
                    'Stagiaire' => 'ROLE_STAGIAIRE',
                    'Employé' => 'ROLE_EMPLOYE',
                    'Utilisateur' => 'ROLE_USER',
                    'Admin' => 'ROLE_ADMIN',
                ],
                'multiple' => true,   
                'expanded' => false,  
                'label' => 'Roles',
                'required' => true,
            ])
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
