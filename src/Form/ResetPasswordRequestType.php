<?php

namespace App\Form;

use App\Entity\ResetPasswordRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResetPasswordRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id')
            ->add('user_id')
            ->add('selector')
            ->add('hashed_token')
            ->add('requested_at', null, [
                'widget' => 'single_text',
            ])
            ->add('expires_at', null, [
                'widget' => 'single_text',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ResetPasswordRequest::class,
        ]);
    }
}
