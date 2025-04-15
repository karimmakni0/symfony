<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 *
 * @method Reservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reservation[]    findAll()
 * @method Reservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function save(Reservation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Reservation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find reservations by user
     */
    public function findByUser($user)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find reservations by activity via billet's activiteId
     */
    public function findByActivity($activity)
    {
        return $this->createQueryBuilder('r')
            ->join('r.billet', 'b')
            ->andWhere('b.activiteId = :activityId')
            ->setParameter('activityId', $activity->getId())
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all reservations with user and billet info
     */
    public function findAllWithDetails()
    {
        return $this->createQueryBuilder('r')
            ->select('r, u, b')
            ->join('r.user', 'u')
            ->join('r.billet', 'b')
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find reservations for multiple activity IDs
     */
    public function findByActivityIds(array $activityIds)
    {
        if (empty($activityIds)) {
            return [];
        }
        
        return $this->createQueryBuilder('r')
            ->join('r.billet', 'b')
            ->andWhere('b.activiteId IN (:activityIds)')
            ->setParameter('activityIds', $activityIds)
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
