<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function isVehiculeReserved($vehiculeId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): bool
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.vehicule = :vehiculeId')
            ->andWhere('r.dateDebut < :endDate')
            ->andWhere('r.dateFin > :startDate')
            ->setParameter('vehiculeId', $vehiculeId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
