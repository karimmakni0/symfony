<?php

namespace App\Repository;

use App\Entity\Coupon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Coupon>
 */
class CouponRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Coupon::class);
    }

    public function save(Coupon $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Coupon $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findValidCoupon(string $code): ?Coupon
    {
        $now = new \DateTimeImmutable();
        
        return $this->createQueryBuilder('c')
            ->where('c.code = :code')
            ->andWhere('c.is_active = :active')
            ->andWhere('c.expires_at > :now')
            ->andWhere('c.usage_count < c.usage_limit')
            ->setParameter('code', strtoupper($code))
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Coupon[]
     */
    public function findAllActive(): array
    {
        $now = new \DateTimeImmutable();
        
        return $this->createQueryBuilder('c')
            ->where('c.is_active = :active')
            ->andWhere('c.expires_at > :now')
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->orderBy('c.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
