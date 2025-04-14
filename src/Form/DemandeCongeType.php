<?php
namespace App\Form;

use App\Entity\DemandeConge;
use App\Entity\Employe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

class DemandeCongeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeConge', ChoiceType::class, [
                'choices' => [
                    'Maladie' => 'MALADIE',
                    'Annuel' => 'ANNUEL',
                    'Maternité' => 'MATERNITE',
                    'Paternité' => 'PATERNITE',
                    'Formation' => 'FORMATION',
                ],
                'label' => 'Type de congé',
                'attr' => ['class' => 'form-select form-control-lg'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un type de congé']),
                ],
            ])
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de début',
                'attr' => ['class' => 'form-control form-control-lg'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir une date de début']),
                    new GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date de début doit être aujourd\'hui ou dans le futur'
                    ]),
                ],
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de fin',
                'attr' => ['class' => 'form-control form-control-lg'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir une date de fin']),
                    new GreaterThanOrEqual([
                        'propertyPath' => 'parent.all[dateDebut].data',
                        'message' => 'La date de fin doit être après la date de début'
                    ]),
                ],
            ])
            ->add('statut', HiddenType::class, [
                'data' => 'EN_ATTENTE',
            ])
            ->add('employe', EntityType::class, [
                'class' => Employe::class,
                'choice_label' => function (Employe $employe) {
                    return $employe->getNom() . ' ' . $employe->getPrenom();
                },
                'label' => 'Employé',
                'placeholder' => 'Sélectionner un employé',
                'attr' => ['class' => 'form-select form-control-lg'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un employé']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DemandeConge::class,
        ]);
    }
}