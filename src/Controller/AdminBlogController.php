<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Comment;
use App\Repository\PostRepository;
use App\Repository\UsersRepository;
use App\Repository\CommentRepository;
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
    private $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        PostRepository $postRepository,
        CommentRepository $commentRepository,
        UsersRepository $usersRepository,
        Security $security
    ) {
        $this->entityManager = $entityManager;
        $this->postRepository = $postRepository;
        $this->commentRepository = $commentRepository;
        $this->usersRepository = $usersRepository;
        $this->security = $security;
    }

    #[Route('/', name: 'app_admin_blog_index')]
    public function index(): Response
    {
        // Check if user has admin role
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'You do not have permission to access this page');
            return $this->redirectToRoute('app_login');
        }

        // Get all posts
        $posts = $this->postRepository->findAll();

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

        return $this->render('admin/blog/index.html.twig', [
            'posts' => $posts,
            'users' => $usersById,
            'activitiesById' => $activitiesById
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
        $comments = $this->commentRepository->findBy(['postId' => $id], ['date' => 'DESC']);

        // Get users for the comments
        $commentUserIds = array_map(function($comment) {
            return $comment->getUserId();
        }, $comments);

        $commentUsers = $this->usersRepository->findBy(['id' => array_unique($commentUserIds)]);
        $commentUsersById = [];
        foreach ($commentUsers as $user) {
            $commentUsersById[$user->getId()] = $user;
        }

        return $this->render('admin/blog/details.html.twig', [
            'post' => $post,
            'author' => $author,
            'activity' => $activity,
            'comments' => $comments,
            'commentUsers' => $commentUsersById
        ]);
    }

    #[Route('/delete/{id}', name: 'app_admin_blog_delete')]
    public function deleteBlog(int $id): Response
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

        // Delete associated comments
        $comments = $this->commentRepository->findBy(['postId' => $id]);
        foreach ($comments as $comment) {
            $this->entityManager->remove($comment);
        }

        // Delete post picture if it exists
        $picture = $post->getPicture();
        if ($picture) {
            $picturePath = $this->getParameter('posts_directory').'/'.$picture;
            if (file_exists($picturePath)) {
                unlink($picturePath);
            }
        }

        // Delete the post
        $this->entityManager->remove($post);
        $this->entityManager->flush();

        $this->addFlash('success', 'Post deleted successfully!');
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
            throw $this->createNotFoundException('Comment not found');
        }

        $postId = $comment->getPostId();
        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        $this->addFlash('success', 'Comment deleted successfully!');
        return $this->redirectToRoute('app_admin_blog_details', ['id' => $postId]);
    }
}
