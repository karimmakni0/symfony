<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class CommentController extends AbstractController
{
    private $entityManager;
    private $commentRepository;
    private $postRepository;
    private $usersRepository;
    private $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommentRepository $commentRepository,
        PostRepository $postRepository,
        UsersRepository $usersRepository,
        Security $security
    ) {
        $this->entityManager = $entityManager;
        $this->commentRepository = $commentRepository;
        $this->postRepository = $postRepository;
        $this->usersRepository = $usersRepository;
        $this->security = $security;
    }

    #[Route('/comment/new/{postId}', name: 'app_comment_new', methods: ['POST'])]
    public function new(Request $request, int $postId): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $post = $this->postRepository->find($postId);
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        $commentText = $request->request->get('comment');
        if (empty($commentText)) {
            $this->addFlash('error', 'Comment cannot be empty');
            return $this->redirectToRoute('app_post_show', ['id' => $postId]);
        }

        $comment = new Comment();
        $comment->setComment($commentText);
        $comment->setUserId($user->getId());
        $comment->setPostId($postId);
        $comment->setDate(new \DateTime());

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        $this->addFlash('success', 'Comment added successfully!');
        return $this->redirectToRoute('app_post_show', ['id' => $postId]);
    }

    #[Route('/comment/edit/{id}', name: 'app_comment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $comment = $this->commentRepository->find($id);
        if (!$comment) {
            throw $this->createNotFoundException('Comment not found');
        }

        // Check if user is authorized to edit this comment
        if ($comment->getUserId() !== $user->getId() && $user->getRole() !== 'Admin') {
            $this->addFlash('error', 'You do not have permission to edit this comment');
            return $this->redirectToRoute('app_post_show', ['id' => $comment->getPostId()]);
        }

        // Handle form submission
        if ($request->isMethod('POST')) {
            $commentText = $request->request->get('comment');
            if (empty($commentText)) {
                $this->addFlash('error', 'Comment cannot be empty');
                return $this->redirectToRoute('app_comment_edit', ['id' => $id]);
            }

            $comment->setComment($commentText);
            $this->entityManager->flush();

            $this->addFlash('success', 'Comment updated successfully!');
            return $this->redirectToRoute('app_post_show', ['id' => $comment->getPostId()]);
        }

        // Get the post for this comment
        $post = $this->postRepository->find($comment->getPostId());

        return $this->render('comment/edit.html.twig', [
            'comment' => $comment,
            'post' => $post,
        ]);
    }

    #[Route('/comment/delete/{id}', name: 'app_comment_delete')]
    public function delete(int $id): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $comment = $this->commentRepository->find($id);
        if (!$comment) {
            throw $this->createNotFoundException('Comment not found');
        }

        // Check if user is authorized to delete this comment
        if ($comment->getUserId() !== $user->getId() && $user->getRole() !== 'Admin') {
            $this->addFlash('error', 'You do not have permission to delete this comment');
            return $this->redirectToRoute('app_post_show', ['id' => $comment->getPostId()]);
        }

        $postId = $comment->getPostId();
        
        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        $this->addFlash('success', 'Comment deleted successfully!');
        return $this->redirectToRoute('app_post_show', ['id' => $postId]);
    }

    #[Route('/comments/user/{userId}', name: 'app_comments_by_user')]
    public function commentsByUser(int $userId): Response
    {
        $user = $this->usersRepository->find($userId);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $comments = $this->commentRepository->findBy(['userId' => $userId], ['date' => 'DESC']);

        // Get posts for these comments
        $postIds = array_map(function($comment) {
            return $comment->getPostId();
        }, $comments);

        $posts = $this->postRepository->findBy(['id' => array_unique($postIds)]);
        $postsById = [];
        foreach ($posts as $post) {
            $postsById[$post->getId()] = $post;
        }

        // Get activities for these posts
        $activityIds = array_map(function($post) {
            return $post->getActivityId();
        }, $posts);

        $activities = $this->entityManager->getRepository('App\\Entity\\Activities')->findBy(['id' => array_unique($activityIds)]);
        $activitiesById = [];
        foreach ($activities as $activity) {
            $activitiesById[$activity->getId()] = $activity;
        }

        return $this->render('comment/by_user.html.twig', [
            'comments' => $comments,
            'profileUser' => $user,
            'posts' => $postsById,
            'activities' => $activitiesById,
        ]);
    }

    #[Route('/admin/comments', name: 'app_admin_comments')]
    public function adminComments(): Response
    {
        $user = $this->security->getUser();
        if (!$user || $user->getRole() !== 'Admin') {
            return $this->redirectToRoute('app_home');
        }

        $comments = $this->commentRepository->findAll();

        // Get users for these comments
        $userIds = array_map(function($comment) {
            return $comment->getUserId();
        }, $comments);

        $users = $this->usersRepository->findBy(['id' => array_unique($userIds)]);
        $usersById = [];
        foreach ($users as $user) {
            $usersById[$user->getId()] = $user;
        }

        // Get posts for these comments
        $postIds = array_map(function($comment) {
            return $comment->getPostId();
        }, $comments);

        $posts = $this->postRepository->findBy(['id' => array_unique($postIds)]);
        $postsById = [];
        foreach ($posts as $post) {
            $postsById[$post->getId()] = $post;
        }

        return $this->render('admin/comments/index.html.twig', [
            'comments' => $comments,
            'users' => $usersById,
            'posts' => $postsById,
        ]);
    }
}
