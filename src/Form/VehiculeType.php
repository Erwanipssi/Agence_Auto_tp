<?php

namespace App\Form;

use App\Entity\Vehicule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehiculeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('immatriculation')
            ->add('marque')
            ->add('modele')
            ->add('type')
            ->add('prix', NumberType::class, [
                'label' => 'Prix par jour (€)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 50',
                ],
                'constraints' => [
                    new Range([
                        'min' => 20,
                        'max' => 50,
                        'notInRangeMessage' => 'Le prix doit être compris entre {{ min }} et {{ max }} euros.',
                    ]),
                ],
            ])
            ->add('statut')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicule::class,
        ]);
    }
}
