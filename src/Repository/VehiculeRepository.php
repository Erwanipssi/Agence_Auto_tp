<?php

namespace App\Repository;

use App\Entity\Vehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VehiculeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicule::class);
    }
    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('v'); // 'v' représente l'entité Vehicule
    
        if (!empty($filters['marque'])) {
            $qb->andWhere('v.marque LIKE :marque')
               ->setParameter('marque', '%' . $filters['marque'] . '%');
        }
    
        if (!empty($filters['modele'])) {
            $qb->andWhere('v.modele LIKE :modele')
               ->setParameter('modele', '%' . $filters['modele'] . '%');
        }
    
        if (!empty($filters['type'])) {
            $qb->andWhere('v.type LIKE :type')
               ->setParameter('type', '%' . $filters['type'] . '%');
        }
    
        return $qb->getQuery()->getResult();
    }
    
    
}

