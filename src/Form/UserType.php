<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cin')
            ->add('tel')
            ->add('nom')
            ->add('prenom')
            ->add('email')
            ->add('password', PasswordType::class) // Password field as secure input
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'RH' => 'ROLE_RH',
                    'Stagiaire' => 'ROLE_STAGIAIRE',
                    'Employé' => 'ROLE_EMPLOYE',
                    'Utilisateur' => 'ROLE_USER',
                    'Admin' => 'ROLE_ADMIN',
                ],
                'multiple' => true,      // allow multiple selections
                'expanded' => true,      // show as checkboxes
                'label' => 'Rôles',
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
