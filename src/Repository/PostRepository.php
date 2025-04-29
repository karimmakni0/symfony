<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 *
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * Find posts by user ID
     *
     * @param int $userId
     * @return Post[]
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find posts by activity ID
     *
     * @param int $activityId
     * @return Post[]
     */
    public function findByActivity(int $activityId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.activityId = :activityId')
            ->setParameter('activityId', $activityId)
            ->orderBy('p.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recent posts with limit
     *
     * @param int $limit
     * @return Post[]
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search posts by title or description
     *
     * @param string $query
     * @return Post[]
     */
    public function searchPosts(string $query): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.title LIKE :query OR p.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('p.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
