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
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/blog')]
class BlogController extends AbstractController
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

    #[Route('/', name: 'app_blog_index')]
    public function index(Request $request): Response
    {
        // Filter by activity if activityId is provided
        $activityId = $request->query->get('activityId');
        
        if ($activityId) {
            $posts = $this->postRepository->findBy(['activityId' => $activityId]);
        } else {
            $posts = $this->postRepository->findAll();
        }

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

        return $this->render('client/Blog/index.html.twig', [
            'posts' => $posts,
            'users' => $usersById,
            'activities' => $activities,
            'activitiesById' => $activitiesById,
            'selectedActivityId' => $activityId
        ]);
    }

    #[Route('/details/{id}', name: 'app_blog_details')]
    public function details(int $id): Response
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

        // Check if current user is the post author (for edit/delete permissions)
        $isAuthor = false;
        $currentUser = $this->security->getUser();
        if ($currentUser && $post->getUserId() === $currentUser->getId()) {
            $isAuthor = true;
        }

        return $this->render('client/Blog/details.html.twig', [
            'post' => $post,
            'author' => $author,
            'activity' => $activity,
            'comments' => $comments,
            'commentUsers' => $commentUsersById,
            'isAuthor' => $isAuthor,
            'currentUser' => $currentUser
        ]);
    }

    #[Route('/add', name: 'app_blog_add')]
    public function addBlog(Request $request, SluggerInterface $slugger): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Get all activities for dropdown
        $activities = $this->entityManager->getRepository('App\\Entity\\Activities')->findAll();

        // Handle form submission
        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $activityId = $request->request->get('activityId');
            $pictureFile = $request->files->get('picture');

            // Check if activity exists
            $activity = $this->entityManager->getRepository('App\\Entity\\Activities')->find($activityId);
            if (!$activity) {
                $this->addFlash('error', 'Activity not found');
                return $this->redirectToRoute('app_blog_add');
            }

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
            return $this->redirectToRoute('app_blog_details', ['id' => $post->getId()]);
        }

        return $this->render('client/Blog/addBlog.html.twig', [
            'activities' => $activities,
        ]);
    }

    #[Route('/edit/{id}', name: 'app_blog_edit')]
    public function editBlog(Request $request, int $id, SluggerInterface $slugger): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $post = $this->postRepository->find($id);
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        // Only the author can edit the post
        if ($post->getUserId() !== $user->getId()) {
            $this->addFlash('error', 'You do not have permission to edit this post');
            return $this->redirectToRoute('app_blog_details', ['id' => $id]);
        }

        // Get all activities for dropdown
        $activities = $this->entityManager->getRepository('App\\Entity\\Activities')->findAll();

        // Handle form submission
        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $activityId = $request->request->get('activityId');
            $pictureFile = $request->files->get('picture');

            // Check if activity exists
            $activity = $this->entityManager->getRepository('App\\Entity\\Activities')->find($activityId);
            if (!$activity) {
                $this->addFlash('error', 'Activity not found');
                return $this->redirectToRoute('app_blog_edit', ['id' => $id]);
            }

            $post->setTitle($title);
            $post->setDescription($description);
            $post->setActivityId($activityId);

            // Handle picture upload if a new picture is provided
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
                    
                    // Delete old picture if it exists
                    $oldPicture = $post->getPicture();
                    if ($oldPicture) {
                        $oldPicturePath = $this->getParameter('posts_directory').'/'.$oldPicture;
                        if (file_exists($oldPicturePath)) {
                            unlink($oldPicturePath);
                        }
                    }
                    
                    $post->setPicture($newFilename);
                } catch (FileException $e) {
                    // Handle exception if something happens during file upload
                    $this->addFlash('error', 'There was a problem uploading your file');
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Post updated successfully!');
            return $this->redirectToRoute('app_blog_details', ['id' => $post->getId()]);
        }

        return $this->render('client/Blog/editBlog.html.twig', [
            'post' => $post,
            'activities' => $activities,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_blog_delete')]
    public function deleteBlog(int $id): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $post = $this->postRepository->find($id);
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        // Only the author can delete the post
        if ($post->getUserId() !== $user->getId()) {
            $this->addFlash('error', 'You do not have permission to delete this post');
            return $this->redirectToRoute('app_blog_details', ['id' => $id]);
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
        return $this->redirectToRoute('app_blog_index');
    }

    #[Route('/comment/add/{postId}', name: 'app_blog_comment_add', methods: ['POST'])]
    public function addComment(Request $request, int $postId): Response
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
            return $this->redirectToRoute('app_blog_details', ['id' => $postId]);
        }

        $comment = new Comment();
        $comment->setComment($commentText);
        $comment->setUserId($user->getId());
        $comment->setPostId($postId);
        $comment->setDate(new \DateTime());

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        $this->addFlash('success', 'Comment added successfully!');
        return $this->redirectToRoute('app_blog_details', ['id' => $postId]);
    }

    #[Route('/comment/edit/{id}', name: 'app_blog_comment_edit', methods: ['POST'])]
    public function editComment(Request $request, int $id): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $comment = $this->commentRepository->find($id);
        if (!$comment) {
            throw $this->createNotFoundException('Comment not found');
        }

        // Only the author can edit the comment
        if ($comment->getUserId() !== $user->getId()) {
            $this->addFlash('error', 'You do not have permission to edit this comment');
            return $this->redirectToRoute('app_blog_details', ['id' => $comment->getPostId()]);
        }

        $commentText = $request->request->get('comment');
        if (empty($commentText)) {
            $this->addFlash('error', 'Comment cannot be empty');
            return $this->redirectToRoute('app_blog_details', ['id' => $comment->getPostId()]);
        }

        $comment->setComment($commentText);
        $this->entityManager->flush();

        $this->addFlash('success', 'Comment updated successfully!');
        return $this->redirectToRoute('app_blog_details', ['id' => $comment->getPostId()]);
    }

    #[Route('/comment/delete/{id}', name: 'app_blog_comment_delete')]
    public function deleteComment(int $id): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $comment = $this->commentRepository->find($id);
        if (!$comment) {
            throw $this->createNotFoundException('Comment not found');
        }

        // Only the author can delete the comment
        if ($comment->getUserId() !== $user->getId()) {
            $this->addFlash('error', 'You do not have permission to delete this comment');
            return $this->redirectToRoute('app_blog_details', ['id' => $comment->getPostId()]);
        }

        $postId = $comment->getPostId();
        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        $this->addFlash('success', 'Comment deleted successfully!');
        return $this->redirectToRoute('app_blog_details', ['id' => $postId]);
    }
}
