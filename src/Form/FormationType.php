<?php
namespace App\Form;

use App\Entity\Formateur;
use App\Entity\Formation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('image')
            ->add('description')
            ->add('is_online')
            ->add('place')
            ->add('lat', NumberType::class)
            ->add('lng', NumberType::class)
            ->add('available_for_employee')
            ->add('available_for_intern')
            ->add('start_date', null, [
                'widget'     => 'single_text',
                'empty_data' => '',
                'required'   => true,
            ])
            ->add('end_date', null, [
                'widget'     => 'single_text',
                'empty_data' => '',
            ])
            ->add('price', NumberType::class)
            ->add('formateur', EntityType::class, [
                'class'        => Formateur::class,
                'choice_label' => function (Formateur $formateur) {
                    return $formateur->getFirstName() . ' ' . $formateur->getLastName();
                },
            ])
            // ->add('employes', EntityType::class, [
            //     'class'        => Employe::class,
            //     'choice_label' => 'id',
            //     'multiple'     => true,
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Formation::class,
        ]);
    }
}
