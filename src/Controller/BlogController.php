<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Comment;
use App\Form\PostFormType;
use App\Form\CommentFormType;
use App\Repository\PostRepository;
use App\Repository\UsersRepository;
use App\Repository\CommentRepository;
use App\Repository\ActivitiesRepository;
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
    private $activitiesRepository;
    private $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        PostRepository $postRepository,
        CommentRepository $commentRepository,
        UsersRepository $usersRepository,
        ActivitiesRepository $activitiesRepository,
        Security $security
    ) {
        $this->entityManager = $entityManager;
        $this->postRepository = $postRepository;
        $this->commentRepository = $commentRepository;
        $this->usersRepository = $usersRepository;
        $this->activitiesRepository = $activitiesRepository;
        $this->security = $security;
    }

    #[Route('/', name: 'app_blog_index')]
    public function index(Request $request): Response
    {
        // Get filters
        $activityId = $request->query->get('activityId');
        $myBlogsOnly = $request->query->getBoolean('myBlogs', false);
        
        $currentUser = $this->security->getUser();
        $userId = $currentUser ? $currentUser->getId() : null;
        
        // Apply filters
        $criteria = [];
        
        if ($activityId) {
            $criteria['activityId'] = $activityId;
        }
        
        if ($myBlogsOnly && $userId) {
            $criteria['userId'] = $userId;
        }
        
        // Get filtered posts
        if (!empty($criteria)) {
            $posts = $this->postRepository->findBy($criteria, ['date' => 'DESC']);
        } else {
            $posts = $this->postRepository->findBy([], ['date' => 'DESC']);
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

        // Get activities for displaying activity details and filter dropdown
        $activities = $this->activitiesRepository->findAll();
        $activitiesById = [];
        foreach ($activities as $activity) {
            $activitiesById[$activity->getId()] = $activity;
        }

        return $this->render('client/Blog/index.html.twig', [
            'posts' => $posts,
            'users' => $usersById,
            'activities' => $activities,
            'activitiesById' => $activitiesById,
            'selectedActivityId' => $activityId,
            'myBlogsOnly' => $myBlogsOnly,
            'currentUserId' => $userId
        ]);
    }

    #[Route('/details/{id}', name: 'app_blog_details')]
    public function details(Request $request, int $id): Response
    {
        $post = $this->postRepository->find($id);
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        // Get post author
        $author = $this->usersRepository->find($post->getUserId());

        // Get activity
        $activity = $this->activitiesRepository->find($post->getActivityId());
        
        // Get resources (images) for the activity
        $resources = [];
        if ($activity) {
            $resources = $activity->getResources();
        }

        // Get comments for this post
        $comments = $this->commentRepository->findBy(['postId' => $id], ['date' => 'DESC']);

        // Get users for the comments
        $commentUserIds = array_map(function($comment) {
            return $comment->getUserId();
        }, $comments);

        $commentUsers = $this->usersRepository->findBy(['id' => array_unique($commentUserIds ? $commentUserIds : [0])]);
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
        
        // Create a new comment instance and form
        $newComment = new Comment();
        $newComment->setPostId($id);
        if ($currentUser) {
            $newComment->setUserId($currentUser->getId());
        }
        
        $commentForm = $this->createForm(CommentFormType::class, $newComment, [
            'action' => $this->generateUrl('app_blog_comment_add', ['postId' => $id]),
            'method' => 'POST',
        ]);
        
        $commentForm->handleRequest($request);
        
        // Create edit forms for each comment
        $editForms = [];
        foreach ($comments as $comment) {
            if ($currentUser && $currentUser->getId() === $comment->getUserId()) {
                $editForm = $this->createForm(CommentFormType::class, $comment, [
                    'action' => $this->generateUrl('app_blog_comment_edit', ['id' => $comment->getId()]),
                    'method' => 'POST',
                ]);
                $editForms[$comment->getId()] = $editForm->createView();
            }
        }
        
        // Get related posts (same activity or same author)
        $relatedPosts = $this->postRepository->findBy([
            'activityId' => $post->getActivityId()
        ], ['date' => 'DESC'], 3);
        
        // Remove the current post from related posts
        $relatedPosts = array_filter($relatedPosts, function($relatedPost) use ($post) {
            return $relatedPost->getId() !== $post->getId();
        });

        return $this->render('client/Blog/details.html.twig', [
            'post' => $post,
            'author' => $author,
            'activity' => $activity,
            'resources' => $resources,
            'comments' => $comments,
            'commentUsers' => $commentUsersById,
            'isAuthor' => $isAuthor,
            'commentForm' => $commentForm->createView(),
            'editForms' => $editForms,
            'relatedPosts' => $relatedPosts,
            'currentUser' => $currentUser
        ]);
    }

    #[Route('/add', name: 'app_blog_add')]
    public function addBlog(Request $request, SluggerInterface $slugger): Response
    {
        // Check if user is logged in
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Create a new post
        $post = new Post();
        $post->setUserId($user->getId());
        
        // Get activities for the dropdown
        $activities = $this->activitiesRepository->findAll();
        $activitiesChoices = [];
        foreach ($activities as $activity) {
            $activitiesChoices[$activity->getActivityName()] = $activity->getId();
        }
        
        // Create form
        $form = $this->createForm(PostFormType::class, $post, [
            'activities_choices' => $activitiesChoices,
            'image_required' => true,
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $pictureFile = $form->get('picture')->getData();
            
            if ($pictureFile) {
                $originalFilename = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$pictureFile->guessExtension();
                
                try {
                    $pictureFile->move(
                        $this->getParameter('posts_directory'),
                        $newFilename
                    );
                    $post->setPicture($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was an error uploading your image.');
                    return $this->redirectToRoute('app_blog_add');
                }
            }
            
            // Set the date
            $post->setDate(new \DateTime());
            
            // Save to database
            $this->entityManager->persist($post);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Your blog post has been published successfully!');
            return $this->redirectToRoute('app_blog_details', [
                'id' => $post->getId()
            ]);
        }
        
        return $this->render('client/Blog/addBlog.html.twig', [
            'form' => $form->createView(),
            'activities' => $activities,
        ]);
    }

    #[Route('/edit/{id}', name: 'app_blog_edit')]
    public function editBlog(Request $request, int $id, SluggerInterface $slugger): Response
    {
        // Check if user is logged in
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Get the post
        $post = $this->postRepository->find($id);
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }
        
        // Check if the current user is the author
        if ($post->getUserId() !== $user->getId()) {
            $this->addFlash('error', 'You do not have permission to edit this post');
            return $this->redirectToRoute('app_blog_details', ['id' => $id]);
        }
        
        // Get activities for the dropdown
        $activities = $this->activitiesRepository->findAll();
        $activitiesChoices = [];
        foreach ($activities as $activity) {
            $activitiesChoices[$activity->getActivityName()] = $activity->getId();
        }
        
        // Create form
        $form = $this->createForm(PostFormType::class, $post, [
            'activities_choices' => $activitiesChoices,
            'image_required' => false, // Image not required for edit
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload only if a new file is provided
            $pictureFile = $form->get('picture')->getData();
            
            if ($pictureFile) {
                $originalFilename = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$pictureFile->guessExtension();
                
                try {
                    $pictureFile->move(
                        $this->getParameter('posts_directory'),
                        $newFilename
                    );
                    
                    // Delete old image if it exists
                    $oldImage = $post->getPicture();
                    if ($oldImage) {
                        $oldImagePath = $this->getParameter('posts_directory').'/'.$oldImage;
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    
                    $post->setPicture($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was an error uploading your image.');
                    return $this->redirectToRoute('app_blog_edit', ['id' => $id]);
                }
            }
            
            // Save to database
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Your blog post has been updated successfully!');
            return $this->redirectToRoute('app_blog_details', [
                'id' => $post->getId()
            ]);
        }
        
        return $this->render('client/Blog/editBlog.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
            'activities' => $activities,
            'posts_directory' => 'uploads/posts'
        ]);
    }

    #[Route('/delete/{id}', name: 'app_blog_delete')]
    public function deleteBlog(int $id): Response
    {
        // Check if user is logged in
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        $post = $this->postRepository->find($id);
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }
        
        // Check if the current user is the author
        if ($post->getUserId() !== $user->getId()) {
            $this->addFlash('error', 'You do not have permission to delete this post');
            return $this->redirectToRoute('app_blog_details', ['id' => $id]);
        }
        
        // Delete all comments related to this post
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

        // Create new comment and form
        $comment = new Comment();
        $comment->setPostId($postId);
        $comment->setUserId($user->getId());
        $comment->setDate(new \DateTime());
        
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $this->addFlash('success', 'Comment added successfully!');
        } elseif ($form->isSubmitted()) {
            // Get form errors and add them as flash messages
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }
        
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

        // Use CommentFormType for validation
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Comment updated successfully!');
        } elseif ($form->isSubmitted()) {
            // Get form errors and add them as flash messages
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

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
