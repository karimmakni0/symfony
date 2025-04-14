<?php

namespace App\Repository;

use App\Entity\Billet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Billet|null find($id, $lockMode = null, $lockVersion = null)
 * @method Billet|null findOneBy(array $criteria, array $orderBy = null)
 * @method Billet[]    findAll()
 * @method Billet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BilletRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Billet::class);
    }

    /**
     * Find all billets associated with a specific activity ID
     *
     * @param int $activityId The ID of the activity
     * @return Billet[] Returns an array of Billet objects
     */
    public function findByActivityId(int $activityId): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.activiteId = :activityId')
            ->setParameter('activityId', $activityId)
            ->orderBy('b.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Calculate the total number of booked tickets for an activity
     *
     * @param int $activityId The ID of the activity
     * @return int Returns the sum of nb field for all billets with the specified activityId
     */
    public function getTotalBookedTicketsForActivity(int $activityId): int
    {
        $result = $this->createQueryBuilder('b')
            ->select('SUM(b.nb) as totalTickets')
            ->andWhere('b.activiteId = :activityId')
            ->setParameter('activityId', $activityId)
            ->getQuery()
            ->getOneOrNullResult();
            
        return $result['totalTickets'] ?? 0;
    }
    
    /**
     * Find all billets with their associated reservations
     *
     * @return array Returns an array of Billet objects with their reservations
     */
    public function findAllWithReservations(): array
    {
        $entityManager = $this->getEntityManager();
        
        $query = $entityManager->createQuery(
            'SELECT b 
            FROM App\Entity\Billet b
            ORDER BY b.id DESC'
        );
        
        return $query->getResult();
    }
    
    /**
     * Find recent billets with their associated reservations
     *
     * @param int $limit Maximum number of results to return
     * @return array Returns an array of Billet objects with their reservations
     */
    public function findRecentWithReservations(int $limit = 5): array
    {
        $entityManager = $this->getEntityManager();
        
        $query = $entityManager->createQuery(
            'SELECT b 
            FROM App\Entity\Billet b
            ORDER BY b.id DESC'
        )->setMaxResults($limit);
        
        return $query->getResult();
    }
}
