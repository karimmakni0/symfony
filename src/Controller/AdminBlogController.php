<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Comment;
use App\Repository\PostRepository;
use App\Repository\UsersRepository;
use App\Repository\CommentRepository;
use App\Repository\BlogRatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/admin/blog')]
class AdminBlogController extends AbstractController
{
    private $entityManager;
    private $postRepository;
    private $commentRepository;
    private $usersRepository;
    private $blogRatingRepository;
    private $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        PostRepository $postRepository,
        CommentRepository $commentRepository,
        UsersRepository $usersRepository,
        BlogRatingRepository $blogRatingRepository,
        Security $security
    ) {
        $this->entityManager = $entityManager;
        $this->postRepository = $postRepository;
        $this->commentRepository = $commentRepository;
        $this->usersRepository = $usersRepository;
        $this->blogRatingRepository = $blogRatingRepository;
        $this->security = $security;
    }

    #[Route('/', name: 'app_admin_blog_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Check if user has admin role
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'You do not have permission to access this page');
            return $this->redirectToRoute('app_login');
        }

        // Get search and filter parameters
        $search = $request->query->get('search');
        $activityId = $request->query->get('activity');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');
        
        // Get pagination parameters
        $page = $request->query->getInt('page', 1);
        $limit = 5; // Show 5 items per page
        
        // Get filtered posts with optional search/filter criteria
        $query = $this->postRepository->createQueryBuilder('p');
        
        // Apply filters if provided
        if ($search) {
            $query->andWhere('p.title LIKE :search OR p.description LIKE :search')
                  ->setParameter('search', '%' . $search . '%');
        }
        
        if ($activityId) {
            $query->andWhere('p.activityId = :activityId')
                  ->setParameter('activityId', $activityId);
        }
        
        if ($dateFrom) {
            $query->andWhere('p.created >= :dateFrom')
                  ->setParameter('dateFrom', new \DateTime($dateFrom));
        }
        
        if ($dateTo) {
            $query->andWhere('p.created <= :dateTo')
                  ->setParameter('dateTo', new \DateTime($dateTo . ' 23:59:59'));
        }
        
        // Get the total number of posts (for pagination)
        $totalPosts = count($query->getQuery()->getResult());
        $totalPages = ceil($totalPosts / $limit);
        
        // Add ordering and pagination
        $query->orderBy('p.date', 'DESC')
              ->setFirstResult(($page - 1) * $limit)
              ->setMaxResults($limit);
        
        // Execute the query
        $posts = $query->getQuery()->getResult();
        
        // For getting all posts (needed for stats)
        $allPosts = $this->postRepository->findAll();
        
        // Extract post IDs
        $postIds = array_map(function($post) {
            return $post->getId();
        }, $posts);

        // Get users for displaying author names
        $userIds = array_map(function($post) {
            return $post->getUserId();
        }, $posts);

        $users = $this->usersRepository->findBy(['id' => array_unique($userIds)]);
        $usersById = [];
        foreach ($users as $user) {
            $usersById[$user->getId()] = $user;
        }

        // Get activities for displaying activity details
        $activityIds = array_map(function($post) {
            return $post->getActivityId();
        }, $posts);

        $activities = $this->entityManager->getRepository('App\\Entity\\Activities')->findAll();
        $activitiesById = [];
        foreach ($activities as $activity) {
            $activitiesById[$activity->getId()] = $activity;
        }
        
        // Get comment counts for each post
        $commentCounts = [];
        $totalComments = 0;
        foreach ($postIds as $postId) {
            $comments = $this->commentRepository->findByPost($postId);
            $commentCounts[$postId] = count($comments);
            $totalComments += count($comments);
        }
        
        // Get like and dislike counts for each post
        $ratingStats = $this->blogRatingRepository->getRatingStatsForPosts($postIds);
        $totalLikes = 0;
        $totalDislikes = 0;
        
        foreach ($ratingStats as $postId => $stats) {
            $totalLikes += $stats['likes'];
            $totalDislikes += $stats['dislikes'];
        }

        return $this->render('admin/blog/index.html.twig', [
            'posts' => $posts,
            'users' => $usersById,
            'activitiesById' => $activitiesById,
            'activities' => $activities, // Pass the full activities array
            'commentCounts' => $commentCounts,
            'ratingStats' => $ratingStats,
            'totalComments' => $totalComments,
            'totalLikes' => $totalLikes,
            'totalDislikes' => $totalDislikes,
            // Pagination data
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalPosts,
            'limit' => $limit
        ]);
    }

    #[Route('/details/{id}', name: 'app_admin_blog_details')]
    public function details(int $id): Response
    {
        // Check if user has admin role
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'You do not have permission to access this page');
            return $this->redirectToRoute('app_login');
        }

        $post = $this->postRepository->find($id);
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        // Get post author
        $author = $this->usersRepository->find($post->getUserId());

        // Get activity
        $activity = $this->entityManager->getRepository('App\\Entity\\Activities')->find($post->getActivityId());

        // Get comments for this post
        $comments = $this->commentRepository->findByPost($post->getId());

        // Get comment authors
        $commentUserIds = array_map(function($comment) {
            return $comment->getUserId();
        }, $comments);

        $commentAuthors = [];
        if (!empty($commentUserIds)) {
            $commentAuthorEntities = $this->usersRepository->findBy(['id' => array_unique($commentUserIds)]);
            foreach ($commentAuthorEntities as $user) {
                $commentAuthors[$user->getId()] = $user;
            }
        }

        // Get like and dislike statistics
        $likesCount = $this->blogRatingRepository->countLikesByPostId($post->getId());
        $dislikesCount = $this->blogRatingRepository->countDislikesByPostId($post->getId());

        return $this->render('admin/blog/details.html.twig', [
            'post' => $post,
            'author' => $author,
            'activity' => $activity,
            'comments' => $comments,
            'commentAuthors' => $commentAuthors,
            'likesCount' => $likesCount,
            'dislikesCount' => $dislikesCount
        ]);
    }

    #[Route('/delete/{id}', name: 'app_admin_blog_delete')]
    public function delete(int $id): Response
    {
        // Check if user has admin role
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'You do not have permission to access this page');
            return $this->redirectToRoute('app_login');
        }

        $post = $this->postRepository->find($id);
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        try {
            // Delete associated comments
            $comments = $this->commentRepository->findByPost($post->getId());
            foreach ($comments as $comment) {
                $this->entityManager->remove($comment);
            }

            // Delete associated ratings
            $ratings = $this->entityManager->getRepository('App\\Entity\\BlogRating')->findBy(['postId' => $post->getId()]);
            foreach ($ratings as $rating) {
                $this->entityManager->remove($rating);
            }

            // Delete post image if exists
            if ($post->getPicture()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/posts/' . $post->getPicture();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            // Delete post
            $this->entityManager->remove($post);
            $this->entityManager->flush();

            $this->addFlash('success', 'Post deleted successfully');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting post: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_blog_index');
    }
    
    #[Route('/comment/delete/{id}', name: 'app_admin_blog_comment_delete')]
    public function deleteComment(int $id): Response
    {
        // Check if user has admin role
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'You do not have permission to access this page');
            return $this->redirectToRoute('app_login');
        }

        $comment = $this->commentRepository->find($id);
        if (!$comment) {
            $this->addFlash('error', 'Comment not found');
            return $this->redirectToRoute('app_admin_blog_index');
        }

        // Store post ID to redirect back to post details page
        $postId = $comment->getPostId();

        try {
            // Remove the comment
            $this->entityManager->remove($comment);
            $this->entityManager->flush();

            $this->addFlash('success', 'Comment successfully deleted');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting comment: ' . $e->getMessage());
        }

        // Redirect to the post details page
        return $this->redirectToRoute('app_admin_blog_details', ['id' => $postId]);
    }
    
    #[Route('/edit/{id}', name: 'app_admin_blog_edit')]
    public function edit(Request $request, int $id): Response
    {
        // Check if user has admin role
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $post = $this->postRepository->find($id);
        if (!$post) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        // Handle form submission
        if ($request->isMethod('POST')) {
            try {
                // Update post properties
                $post->setTitle($request->request->get('title'));
                $post->setDescription($request->request->get('description'));
                
                // Update activity if provided
                $activityId = $request->request->get('activityId');
                if ($activityId) {
                    $post->setActivityId($activityId);
                }
                
                // Handle image upload
                $pictureFile = $request->files->get('picture');
                if ($pictureFile) {
                    // Delete old image if exists
                    if ($post->getPicture()) {
                        $oldImagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/posts/' . $post->getPicture();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    
                    // Generate a unique filename
                    $newFilename = uniqid() . '.' . $pictureFile->guessExtension();
                    
                    // Move the file to the posts directory
                    try {
                        $pictureFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/posts',
                            $newFilename
                        );
                        
                        // Update the post entity with the new filename
                        $post->setPicture($newFilename);
                    } catch (FileException $e) {
                        return $this->json(['error' => 'Error uploading image: ' . $e->getMessage()], 500);
                    }
                }
                
                // Handle image removal
                if ($request->request->get('removeImage') && $post->getPicture()) {
                    // Delete the image file
                    $imagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/posts/' . $post->getPicture();
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                    
                    // Remove the filename from the post entity
                    $post->setPicture(null);
                }
                
                // Save changes
                $this->entityManager->persist($post);
                $this->entityManager->flush();
                
                return $this->json(['success' => true, 'message' => 'Post updated successfully']);
            } catch (\Exception $e) {
                return $this->json(['error' => 'Error updating post: ' . $e->getMessage()], 500);
            }
        }

        // For direct access (not AJAX), redirect to details page
        return $this->redirectToRoute('app_admin_blog_details', ['id' => $id]);
    }
}
