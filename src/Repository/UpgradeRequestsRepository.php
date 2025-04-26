<?php

namespace App\Repository;

use App\Entity\UpgradeRequests;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UpgradeRequests|null find($id, $lockMode = null, $lockVersion = null)
 * @method UpgradeRequests|null findOneBy(array $criteria, array $orderBy = null)
 * @method UpgradeRequests[]    findAll()
 * @method UpgradeRequests[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UpgradeRequestsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UpgradeRequests::class);
    }

    /**
     * Find an upgrade request by user ID
     */
    public function findByUser($user): ?UpgradeRequests
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.user = :user')
            ->setParameter('user', $user)
            ->orderBy('u.request_date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    /**
     * Filter upgrade requests based on criteria
     * 
     * @param array $filters Filters to apply
     * @return UpgradeRequests[] Returns an array of UpgradeRequests objects
     */
    public function filterRequests(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')
            ->addSelect('u');
        
        // Apply search term filter (searches in user name, lastname, and email)
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $qb->andWhere('u.name LIKE :search OR u.lastname LIKE :search OR u.email LIKE :search')
               ->setParameter('search', $searchTerm);
        }
        
        // Filter by status
        if (!empty($filters['status'])) {
            $qb->andWhere('r.status = :status')
               ->setParameter('status', $filters['status']);
        }
        
        // Filter by date range (if provided)
        if (!empty($filters['date_from'])) {
            $qb->andWhere('r.request_date >= :dateFrom')
               ->setParameter('dateFrom', new \DateTime($filters['date_from']));
        }
        
        if (!empty($filters['date_to'])) {
            $qb->andWhere('r.request_date <= :dateTo')
               ->setParameter('dateTo', new \DateTime($filters['date_to'] . ' 23:59:59'));
        }
        
        // Sort requests by date, newest first
        $qb->orderBy('r.request_date', 'DESC');
        
        return $qb->getQuery()->getResult();
    }
}
