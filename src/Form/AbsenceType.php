<?php

namespace App\Form;

use App\Entity\Absence;
use App\Entity\Employe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class AbsenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('motif', ChoiceType::class, [
                'choices' => [
                    'Maladie' => 'MALADIE',
                    'Congé' => 'CONGE',
                    'Autre' => 'AUTRE',
                ],
                'placeholder' => 'Sélectionnez un motif',
            ])
            ->add('justificatif', FileType::class, [
                'label' => 'Justificatif (PDF, PNG, JPEG)',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/pdf',
                            'image/png',
                            'image/jpeg',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier PDF, JPEG ou PNG valide.',
                    ]),
                    new NotBlank([
                        'message' => 'Un justificatif est requis.',
                    ]),
                ],
            ])
            ->add('remarque')
            ->add('employe', EntityType::class, [
                'class' => Employe::class,
                'choice_label' => fn(Employe $employe) => $employe->getNom() . ' ' . $employe->getPrenom(),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Absence::class,
        ]);
    }
}
