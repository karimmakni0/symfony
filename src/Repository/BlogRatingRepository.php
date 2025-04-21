<?php

namespace App\Repository;

use App\Entity\BlogRating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BlogRating>
 *
 * @method BlogRating|null find($id, $lockMode = null, $lockVersion = null)
 * @method BlogRating|null findOneBy(array $criteria, array $orderBy = null)
 * @method BlogRating[]    findAll()
 * @method BlogRating[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BlogRatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlogRating::class);
    }

    /**
     * Find a rating by user ID and post ID
     */
    public function findByUserAndPost(int $userId, int $postId): ?BlogRating
    {
        return $this->findOneBy(['userId' => $userId, 'postId' => $postId]);
    }

    /**
     * Count likes for a specific post
     */
    public function countLikesByPostId(int $postId): int
    {
        return $this->count([
            'postId' => $postId,
            'isLike' => true
        ]);
    }

    /**
     * Count dislikes for a specific post
     */
    public function countDislikesByPostId(int $postId): int
    {
        return $this->count([
            'postId' => $postId,
            'isLike' => false
        ]);
    }

    /**
     * Get rating statistics for multiple posts
     * Returns an associative array with postId as key and nested arrays for likes and dislikes counts
     */
    public function getRatingStatsForPosts(array $postIds): array
    {
        if (empty($postIds)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('br')
            ->select('br.postId, br.isLike, COUNT(br.id) as count')
            ->where('br.postId IN (:postIds)')
            ->setParameter('postIds', $postIds)
            ->groupBy('br.postId, br.isLike');

        $results = $queryBuilder->getQuery()->getResult();
        
        // Initialize stats for all posts
        $stats = [];
        foreach ($postIds as $postId) {
            $stats[$postId] = [
                'likes' => 0,
                'dislikes' => 0
            ];
        }
        
        // Fill in the actual counts
        foreach ($results as $result) {
            $postId = $result['postId'];
            $isLike = $result['isLike'];
            $count = $result['count'];
            
            if ($isLike) {
                $stats[$postId]['likes'] = $count;
            } else {
                $stats[$postId]['dislikes'] = $count;
            }
        }
        
        return $stats;
    }
}
