<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Entity\Vehicule;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateDebut', null, [
                'widget' => 'single_text',
                'label' => 'Date de début',
                'html5' => true,
            ])
            ->add('dateFin', null, [
                'widget' => 'single_text',
                'label' => 'Date de fin',
                'html5' => true,
            ])
        
            
            
            ->add('vehicule', EntityType::class, [
                'class' => Vehicule::class,
                'choice_label' => function (Vehicule $vehicule) {
                    return $vehicule->getMarque() . ' - ' . $vehicule->getModele() . ' (' . $vehicule->getImmatriculation() . ')';
                },
                'label' => 'Véhicule',
            ])
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
