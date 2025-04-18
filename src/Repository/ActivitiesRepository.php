<?php

namespace App\Repository;

use App\Entity\Activities;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Activities|null find($id, $lockMode = null, $lockVersion = null)
 * @method Activities|null findOneBy(array $criteria, array $orderBy = null)
 * @method Activities[]    findAll()
 * @method Activities[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivitiesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activities::class);
    }
    
    /**
     * Find all activities with their associated resources
     * 
     * @return Activities[]
     */
    public function findAllWithResources()
    {
        return $this->createQueryBuilder('a')
            ->select('a', 'r')
            ->leftJoin('a.resources', 'r')
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all unique destinations for filtering
     * 
     * @return array List of unique destinations
     */
    public function findAllDestinations()
    {
        $result = $this->createQueryBuilder('a')
            ->select('DISTINCT a.activity_destination')
            ->where('a.activity_destination IS NOT NULL')
            ->andWhere('a.activity_destination != :empty')
            ->setParameter('empty', '')
            ->orderBy('a.activity_destination', 'ASC')
            ->getQuery()
            ->getScalarResult();
        
        // Extract the values from the result array
        $destinations = array_map(function($item) {
            return $item['activity_destination'];
        }, $result);
        
        return $destinations;
    }

    /**
     * Get activity types with counts
     * 
     * @return array Associative array of activity types and their counts
     */
    public function findActivityTypesWithCount()
    {
        $result = $this->createQueryBuilder('a')
            ->select('a.activity_genre, COUNT(a.id) as count')
            ->where('a.activity_genre IS NOT NULL')
            ->andWhere('a.activity_genre != :empty')
            ->setParameter('empty', '')
            ->groupBy('a.activity_genre')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getScalarResult();
        
        // Convert to associative array
        $activityTypes = [];
        foreach ($result as $item) {
            $activityTypes[$item['activity_genre']] = $item['count'];
        }
        
        return $activityTypes;
    }

    /**
     * Get durations with counts
     * 
     * @return array Associative array of durations and their counts
     */
    public function findDurationsWithCount()
    {
        $result = $this->createQueryBuilder('a')
            ->select('a.activity_duration, COUNT(a.id) as count')
            ->where('a.activity_duration IS NOT NULL')
            ->andWhere('a.activity_duration != :empty')
            ->setParameter('empty', '')
            ->groupBy('a.activity_duration')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getScalarResult();
        
        // Convert to associative array
        $durations = [];
        foreach ($result as $item) {
            $durations[$item['activity_duration']] = $item['count'];
        }
        
        return $durations;
    }

    /**
     * Find activities by filter criteria
     * 
     * @param string|null $destination Destination filter
     * @param string|null $startDate Start date filter (Y-m-d format)
     * @param string|null $endDate End date filter (Y-m-d format)
     * @param float|null $minPrice Minimum price filter
     * @param float|null $maxPrice Maximum price filter
     * @param array $activityTypes Activity types filter
     * @param array $durations Duration filter
     * @return Activities[] Filtered activities
     */
    public function findByFilters(
        ?string $destination = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        array $activityTypes = [],
        array $durations = []
    ) {
        $qb = $this->createQueryBuilder('a')
            ->select('a', 'r')
            ->leftJoin('a.resources', 'r');
        
        // Apply destination filter
        if (!empty($destination)) {
            $qb->andWhere('a.activity_destination = :destination')
               ->setParameter('destination', $destination);
        }
        
        // Apply date range filter if both dates are provided
        if (!empty($startDate) && !empty($endDate)) {
            try {
                $startDateObj = new \DateTime($startDate);
                $endDateObj = new \DateTime($endDate);
                
                $qb->andWhere('a.activity_date >= :startDate')
                   ->andWhere('a.activity_date <= :endDate')
                   ->setParameter('startDate', $startDateObj->format('Y-m-d'))
                   ->setParameter('endDate', $endDateObj->format('Y-m-d'));
            } catch (\Exception $e) {
                // Invalid date format, ignore filter
            }
        }
        
        // Apply price range filter
        if (!empty($minPrice)) {
            $qb->andWhere('a.activity_price >= :minPrice')
               ->setParameter('minPrice', $minPrice);
        }
        
        if (!empty($maxPrice)) {
            $qb->andWhere('a.activity_price <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }
        
        // Apply activity types filter
        if (!empty($activityTypes)) {
            $qb->andWhere('a.activity_genre IN (:activityTypes)')
               ->setParameter('activityTypes', $activityTypes);
        }
        
        // Apply duration filter
        if (!empty($durations)) {
            $qb->andWhere('a.activity_duration IN (:durations)')
               ->setParameter('durations', $durations);
        }
        
        // Order by ID descending (newest first)
        $qb->orderBy('a.id', 'DESC');
        
        return $qb->getQuery()->getResult();
    }
}
