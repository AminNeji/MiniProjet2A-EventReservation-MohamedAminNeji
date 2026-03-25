<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function findByEvent(Event $event): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.event = :event')
            ->setParameter('event', $event)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByEvent(Event $event): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.event = :event')
            ->setParameter('event', $event)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
