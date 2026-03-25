<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function findUpcoming(): array
    {
        $startOfToday = (new \DateTimeImmutable('now', new \DateTimeZone('Africa/Tunis')))->setTime(0, 0, 0);

        return $this->createQueryBuilder('e')
            ->andWhere('e.date >= :startOfToday')
            ->setParameter('startOfToday', $startOfToday)
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
