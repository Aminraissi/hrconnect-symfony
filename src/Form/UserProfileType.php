<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class UserProfileType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cin')
            ->add('tel')
            ->add('nom')
            ->add('prenom')
            ->add('email')
            ->add('profilePicture', FileType::class, [
                'label' => 'Profile Picture (JPEG or PNG)',
                'mapped' => false,
                'required' => false,
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'first_options'  => ['label' => 'Password (leave blank to keep current)'],
                'second_options' => ['label' => 'Repeat Password'],
            ])
        ;
    }
    
}