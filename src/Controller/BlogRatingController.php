<?php

namespace App\Controller;

use App\Entity\BlogRating;
use App\Repository\BlogRatingRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/blog/rating')]
class BlogRatingController extends AbstractController
{
    private $entityManager;
    private $blogRatingRepository;
    private $postRepository;
    private $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        BlogRatingRepository $blogRatingRepository,
        PostRepository $postRepository,
        Security $security
    ) {
        $this->entityManager = $entityManager;
        $this->blogRatingRepository = $blogRatingRepository;
        $this->postRepository = $postRepository;
        $this->security = $security;
    }

    #[Route('/toggle/{postId}/{isLike}', name: 'app_blog_rating_toggle', methods: ['POST'])]
    public function toggleRating(Request $request, int $postId, bool $isLike): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $userId = $user->getId();
        
        // Check if post exists
        $post = $this->postRepository->find($postId);
        if (!$post) {
            return new JsonResponse(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if user already rated this post
        $existingRating = $this->blogRatingRepository->findByUserAndPost($userId, $postId);
        
        if ($existingRating) {
            // User already rated this post
            if ($existingRating->isLike() === $isLike) {
                // User clicked the same button again, so remove the rating
                $this->entityManager->remove($existingRating);
                $this->entityManager->flush();
                $action = 'removed';
            } else {
                // User changed their mind, update the rating
                $existingRating->setIsLike($isLike);
                $existingRating->setUpdatedAt(new \DateTime());
                $this->entityManager->flush();
                $action = 'updated';
            }
        } else {
            // Create new rating
            $rating = new BlogRating();
            $rating->setUserId($userId);
            $rating->setPostId($postId);
            $rating->setIsLike($isLike);
            
            $this->entityManager->persist($rating);
            $this->entityManager->flush();
            $action = 'added';
        }

        // Get updated counts
        $likesCount = $this->blogRatingRepository->countLikesByPostId($postId);
        $dislikesCount = $this->blogRatingRepository->countDislikesByPostId($postId);

        return new JsonResponse([
            'success' => true,
            'action' => $action,
            'postId' => $postId,
            'likesCount' => $likesCount,
            'dislikesCount' => $dislikesCount,
            'userRating' => $action !== 'removed' ? ($isLike ? 'like' : 'dislike') : null
        ]);
    }

    #[Route('/status/{postId}', name: 'app_blog_rating_status', methods: ['GET'])]
    public function getRatingStatus(int $postId): JsonResponse
    {
        $user = $this->security->getUser();
        $userId = $user ? $user->getId() : null;
        
        // Get post rating status
        $likesCount = $this->blogRatingRepository->countLikesByPostId($postId);
        $dislikesCount = $this->blogRatingRepository->countDislikesByPostId($postId);
        
        // Get user's current rating if authenticated
        $userRating = null;
        if ($userId) {
            $existingRating = $this->blogRatingRepository->findByUserAndPost($userId, $postId);
            if ($existingRating) {
                $userRating = $existingRating->isLike() ? 'like' : 'dislike';
            }
        }

        return new JsonResponse([
            'postId' => $postId,
            'likesCount' => $likesCount,
            'dislikesCount' => $dislikesCount,
            'userRating' => $userRating
        ]);
    }
}
