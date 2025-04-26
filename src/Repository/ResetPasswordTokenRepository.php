<?php

namespace App\Repository;

use App\Entity\ResetPasswordToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;

/**
 * @extends ServiceEntityRepository<ResetPasswordToken>
 */
class ResetPasswordTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResetPasswordToken::class);
    }

    public function save(ResetPasswordToken $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ResetPasswordToken $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findValidToken(string $token): ?ResetPasswordToken
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.token = :token')
            ->andWhere('r.expiresAt > :now')
            ->andWhere('r.isUsed = :isUsed')
            ->setParameter('token', $token)
            ->setParameter('now', new DateTime())
            ->setParameter('isUsed', false)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByEmail(string $email): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getResult();
    }

    public function cleanExpiredTokens(): void
    {
        $this->createQueryBuilder('r')
            ->delete()
            ->andWhere('r.expiresAt < :now')
            ->setParameter('now', new DateTime())
            ->getQuery()
            ->execute();
    }
}
