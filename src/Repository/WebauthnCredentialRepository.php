<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WebauthnCredentialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebauthnCredential::class);
    }

    public function findByCredentialId(string $credentialId): ?WebauthnCredential
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.credentialId = :credentialId')
            ->setParameter('credentialId', $credentialId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function saveCredential(User $user, string $credentialId, string $credentialData, string $name = 'Ma clé'): WebauthnCredential
    {
        $credential = new WebauthnCredential();
        $credential->setUser($user);
        $credential->setCredentialId($credentialId);
        $credential->setCredentialData($credentialData);
        $credential->setName($name);

        $this->getEntityManager()->persist($credential);
        $this->getEntityManager()->flush();

        return $credential;
    }
}
