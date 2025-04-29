<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Users;
use App\Repository\PostRepository;
use App\Repository\CommentRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\String\Slugger\SluggerInterface;

class PostController extends AbstractController
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

    #[Route('/posts', name: 'app_posts_index')]
    public function index(): Response
    {
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

        $activities = $this->entityManager->getRepository('App\\Entity\\Activities')->findBy(['id' => array_unique($activityIds)]);
        $activitiesById = [];
        foreach ($activities as $activity) {
            $activitiesById[$activity->getId()] = $activity;
        }

        return $this->render('post/index.html.twig', [
            'posts' => $posts,
            'users' => $usersById,
            'activities' => $activitiesById,
        ]);
    }

    #[Route('/post/new/{activityId}', name: 'app_post_new')]
    public function new(Request $request, int $activityId, SluggerInterface $slugger): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Check if activity exists
        $activity = $this->entityManager->getRepository('App\\Entity\\Activities')->find($activityId);
        if (!$activity) {
            $this->addFlash('error', 'Activity not found');
            return $this->redirectToRoute('app_home');
        }

        // Handle form submission
        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $pictureFile = $request->files->get('picture');

            $post = new Post();
            $post->setTitle($title);
            $post->setDescription($description);
            $post->setUserId($user->getId());
            $post->setActivityId($activityId);
            $post->setDate(new \DateTime());

            // Handle picture upload
            if ($pictureFile) {
                $originalFilename = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // Use slugger to create a safe filename
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$pictureFile->guessExtension();

                // Move the file to the uploads directory
                try {
                    $pictureFile->move(
                        $this->getParameter('posts_directory'),
                        $newFilename
                    );
                    $post->setPicture($newFilename);
                } catch (FileException $e) {
                    // Handle exception if something happens during file upload
                    $this->addFlash('error', 'There was a problem uploading your file');
                }
            }

            $this->entityManager->persist($post);
            $this->entityManager->flush();

            $this->addFlash('success', 'Post created successfully!');
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/new.html.twig', [
            'activity' => $activity,
        ]);
    }

    #[Route('/post/{id}', name: 'app_post_show')]
    public function show(int $id): Response
    {
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

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'author' => $author,
            'activity' => $activity,
            'comments' => $comments,
            'commentUsers' => $commentUsersById,
        ]);
    }

    #[Route('/post/edit/{id}', name: 'app_post_edit')]
    public function edit(Request $request, int $id, SluggerInterface $slugger): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $post = $this->postRepository->find($id);
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        // Check if user is authorized to edit this post
        if ($post->getUserId() !== $user->getId() && $user->getRole() !== 'Admin') {
            $this->addFlash('error', 'You do not have permission to edit this post');
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        // Handle form submission
        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $pictureFile = $request->files->get('picture');

            $post->setTitle($title);
            $post->setDescription($description);

            // Handle picture upload if a new one is provided
            if ($pictureFile) {
                $originalFilename = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$pictureFile->guessExtension();

                try {
                    $pictureFile->move(
                        $this->getParameter('posts_directory'),
                        $newFilename
                    );
                    
                    // Remove old picture if exists
                    if ($post->getPicture()) {
                        $oldPicturePath = $this->getParameter('posts_directory').'/'.$post->getPicture();
                        if (file_exists($oldPicturePath)) {
                            unlink($oldPicturePath);
                        }
                    }
                    
                    $post->setPicture($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was a problem uploading your file');
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Post updated successfully!');
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        // Get activity
        $activity = $this->entityManager->getRepository('App\\Entity\\Activities')->find($post->getActivityId());

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'activity' => $activity,
        ]);
    }

    #[Route('/post/delete/{id}', name: 'app_post_delete')]
    public function delete(int $id): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $post = $this->postRepository->find($id);
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        // Check if user is authorized to delete this post
        if ($post->getUserId() !== $user->getId() && $user->getRole() !== 'Admin') {
            $this->addFlash('error', 'You do not have permission to delete this post');
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        // Remove picture file if exists
        if ($post->getPicture()) {
            $picturePath = $this->getParameter('posts_directory').'/'.$post->getPicture();
            if (file_exists($picturePath)) {
                unlink($picturePath);
            }
        }

        // Delete all comments associated with this post
        $comments = $this->commentRepository->findBy(['postId' => $id]);
        foreach ($comments as $comment) {
            $this->entityManager->remove($comment);
        }

        $this->entityManager->remove($post);
        $this->entityManager->flush();

        $this->addFlash('success', 'Post deleted successfully!');
        return $this->redirectToRoute('app_posts_index');
    }

    #[Route('/posts/activity/{activityId}', name: 'app_posts_by_activity')]
    public function postsByActivity(int $activityId): Response
    {
        $activity = $this->entityManager->getRepository('App\\Entity\\Activities')->find($activityId);
        if (!$activity) {
            throw $this->createNotFoundException('Activity not found');
        }

        $posts = $this->postRepository->findBy(['activityId' => $activityId]);

        // Get users for displaying author names
        $userIds = array_map(function($post) {
            return $post->getUserId();
        }, $posts);

        $users = $this->usersRepository->findBy(['id' => array_unique($userIds)]);
        $usersById = [];
        foreach ($users as $user) {
            $usersById[$user->getId()] = $user;
        }

        return $this->render('post/by_activity.html.twig', [
            'posts' => $posts,
            'activity' => $activity,
            'users' => $usersById,
        ]);
    }

    #[Route('/posts/user/{userId}', name: 'app_posts_by_user')]
    public function postsByUser(int $userId): Response
    {
        $user = $this->usersRepository->find($userId);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $posts = $this->postRepository->findBy(['userId' => $userId]);

        // Get activities for displaying activity details
        $activityIds = array_map(function($post) {
            return $post->getActivityId();
        }, $posts);

        $activities = $this->entityManager->getRepository('App\\Entity\\Activities')->findBy(['id' => array_unique($activityIds)]);
        $activitiesById = [];
        foreach ($activities as $activity) {
            $activitiesById[$activity->getId()] = $activity;
        }

        return $this->render('post/by_user.html.twig', [
            'posts' => $posts,
            'profileUser' => $user,
            'activities' => $activitiesById,
        ]);
    }
}
