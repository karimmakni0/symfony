<?php

namespace App\Repository;

use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method Users|null find($id, $lockMode = null, $lockVersion = null)
 * @method Users|null findOneBy(array $criteria, array $orderBy = null)
 * @method Users[]    findAll()
 * @method Users[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UsersRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Users::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Users) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Find a user by email
     */
    public function findByEmail(string $email): ?Users
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Find a user by verification code
     */
    public function findByVerificationCode(string $verificationCode): ?Users
    {
        return $this->findOneBy(['verification_code' => $verificationCode]);
    }
    
    /**
     * Filter users based on search criteria
     * 
     * @param array $filters Filters to apply
     * @return Users[] Returns an array of Users objects
     */
    public function filterUsers(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('u');
        
        // Apply search term filter (searches in name, lastname, and email)
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $qb->andWhere('u.name LIKE :search OR u.lastname LIKE :search OR u.email LIKE :search')
               ->setParameter('search', $searchTerm);
        }
        
        // Filter by role
        if (!empty($filters['role'])) {
            $qb->andWhere('u.role = :role')
               ->setParameter('role', $filters['role']);
        }
        
        // Filter by status (banned/active)
        if (isset($filters['status'])) {
            $qb->andWhere('u.isBanned = :status')
               ->setParameter('status', $filters['status'] === 'banned');
        }
        
        // Sort users
        $qb->orderBy('u.id', 'ASC');
        
        return $qb->getQuery()->getResult();
    }
}
