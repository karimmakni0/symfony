<?php

namespace App\Repository;

use App\Entity\UserLoginHistory;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserLoginHistory>
 */
class UserLoginHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserLoginHistory::class);
    }

    /**
     * Find the most recent login by a specific user
     */
    public function findMostRecentLogin(Users $user): ?UserLoginHistory
    {
        return $this->createQueryBuilder('l')
            ->where('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('l.loginTime', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    /**
     * Find all logins for a user from a specific IP address
     */
    public function findLoginsByIp(Users $user, string $ipAddress): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.user = :user')
            ->andWhere('l.ipAddress = :ipAddress')
            ->setParameter('user', $user)
            ->setParameter('ipAddress', $ipAddress)
            ->orderBy('l.loginTime', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find if a login with this user and device combination exists
     */
    public function findMatchingLogin(Users $user, string $ipAddress, string $userAgent): ?UserLoginHistory
    {
        return $this->createQueryBuilder('l')
            ->where('l.user = :user')
            ->andWhere('l.ipAddress = :ipAddress')
            ->andWhere('l.userAgent = :userAgent')
            ->setParameter('user', $user)
            ->setParameter('ipAddress', $ipAddress)
            ->setParameter('userAgent', $userAgent)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
