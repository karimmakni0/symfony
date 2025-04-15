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
}
