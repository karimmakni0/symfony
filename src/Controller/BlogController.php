<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Comment;
use App\Form\PostFormType;
use App\Form\CommentFormType;
use App\Repository\PostRepository;
use App\Repository\UsersRepository;
use App\Repository\CommentRepository;
use App\Repository\BlogRatingRepository;
use App\Repository\ActivitiesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

#[Route('/blog')]
class BlogController extends AbstractController
{
    private $entityManager;
    private $postRepository;
    private $commentRepository;
    private $usersRepository;
    private $activitiesRepository;
    private $blogRatingRepository;
    private $security;
    private $httpClient;
    private $params;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        PostRepository $postRepository,
        CommentRepository $commentRepository,
        UsersRepository $usersRepository,
        ActivitiesRepository $activitiesRepository,
        BlogRatingRepository $blogRatingRepository,
        Security $security,
        HttpClientInterface $httpClient,
        ParameterBagInterface $params,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->postRepository = $postRepository;
        $this->commentRepository = $commentRepository;
        $this->usersRepository = $usersRepository;
        $this->activitiesRepository = $activitiesRepository;
        $this->blogRatingRepository = $blogRatingRepository;
        $this->security = $security;
        $this->httpClient = $httpClient;
        $this->params = $params;
        $this->logger = $logger;
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
        
        // Get rating statistics for all posts
        $postIds = array_map(function($post) {
            return $post->getId();
        }, $posts);
        
        $ratingStats = $this->blogRatingRepository->getRatingStatsForPosts($postIds);

        return $this->render('client/Blog/index.html.twig', [
            'posts' => $posts,
            'users' => $usersById,
            'activities' => $activities,
            'activitiesById' => $activitiesById,
            'selectedActivityId' => $activityId,
            'myBlogsOnly' => $myBlogsOnly,
            'currentUserId' => $userId,
            'ratingStats' => $ratingStats
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
        $comments = $this->commentRepository->findBy(['postId' => $id], ['date' => 'ASC']);
        
        // Get users for displaying comment author names
        $commentUserIds = array_map(function($comment) {
            return $comment->getUserId();
        }, $comments);
        
        $commentUsers = $this->usersRepository->findBy(['id' => array_unique($commentUserIds)]);
        $commentUsersById = [];
        foreach ($commentUsers as $user) {
            $commentUsersById[$user->getId()] = $user;
        }

        // Create form for new comment
        $newComment = new Comment();
        $newComment->setPostId($id);
        $commentForm = $this->createForm(CommentFormType::class, $newComment);
        $commentForm->handleRequest($request);
        
        // Handle comment form submission
        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $user = $this->security->getUser();
            if (!$user) {
                return $this->redirectToRoute('app_login');
            }
            
            $newComment->setUserId($user->getId());
            $newComment->setDate(new \DateTime());
            
            $this->entityManager->persist($newComment);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Comment added successfully!');
            return $this->redirectToRoute('app_blog_details', ['id' => $id]);
        }
        
        // Create forms for editing comments
        $editForms = [];
        foreach ($comments as $comment) {
            $form = $this->createForm(CommentFormType::class, $comment, [
                'action' => $this->generateUrl('app_blog_comment_edit', ['id' => $comment->getId()]),
            ]);
            $editForms[$comment->getId()] = $form->createView();
        }

        // Check if current user is the author of the post
        $currentUser = $this->security->getUser();
        $isAuthor = $currentUser && $post->getUserId() === $currentUser->getId();
        
        // Get blog rating information
        $likesCount = $this->blogRatingRepository->countLikesByPostId($id);
        $dislikesCount = $this->blogRatingRepository->countDislikesByPostId($id);
        
        // Get user's current rating if authenticated
        $userRating = null;
        if ($currentUser) {
            $existingRating = $this->blogRatingRepository->findByUserAndPost($currentUser->getId(), $id);
            if ($existingRating) {
                $userRating = $existingRating->isLike() ? 'like' : 'dislike';
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
            'resources' => $resources ?? [],
            'comments' => $comments,
            'commentUsers' => $commentUsersById,
            'commentForm' => $commentForm->createView(),
            'editForms' => $editForms,
            'isAuthor' => $isAuthor,
            'likesCount' => $likesCount,
            'dislikesCount' => $dislikesCount,
            'userRating' => $userRating,
            'relatedPosts' => $relatedPosts
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

    #[Route('/generate-content', name: 'app_blog_generate_content', methods: ['POST'])]
    public function generateContent(Request $request): JsonResponse
    {
        // Check if user is logged in
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'You must be logged in'], Response::HTTP_UNAUTHORIZED);
        }
        
        // Get the activity ID from the request
        $activityId = $request->request->get('activityId');
        if (!$activityId) {
            return new JsonResponse(['error' => 'Activity must be selected'], Response::HTTP_BAD_REQUEST);
        }
        
        // Get activity details
        $activity = $this->activitiesRepository->find($activityId);
        if (!$activity) {
            return new JsonResponse(['error' => 'Activity not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Get the uploaded image
        $imageFile = $request->files->get('image');
        if (!$imageFile) {
            return new JsonResponse(['error' => 'Image must be uploaded'], Response::HTTP_BAD_REQUEST);
        }
        
        // Convert image to base64
        $imageData = base64_encode(file_get_contents($imageFile->getPathname()));
        
        // Build a more structured prompt with detailed instructions
        $activityName = $activity->getActivityName();
        $activityDestination = $activity->getActivityDestination();
        $prompt = "Create an engaging and vivid travel blog post about {$activityName} in {$activityDestination} based on the uploaded image.

IMPORTANT: Respond ONLY with the blog content. Do not include any introductions or explanations.

Your blog post should:
1. Have a catchy title in bold (using Markdown **Title**)
2. Be 2-3 paragraphs long (200-300 words)
3. Include specific details about {$activityName} that are visible in the image
4. Incorporate the location ({$activityDestination}) naturally in the narrative
5. Use an enthusiastic, first-person perspective
6. Evoke emotions and sensory details (sights, sounds, feelings)
7. End with a compelling reason for readers to try this activity

REMEMBER: Return ONLY the formatted blog content without any introductory text like 'Here's a blog post' or 'Okay, here's a description.'";
        
        // Try three different model endpoints to see which one works - using latest v1beta API
        $modelEndpoints = [
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro-vision:generateContent',
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent',
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro-vision:generateContent'
        ];
        
        $apiResponse = null;
        $lastError = null;
        
        // Try each endpoint until one works
        foreach ($modelEndpoints as $endpoint) {
            try {
                $this->logger->info('Trying Gemini endpoint', ['endpoint' => $endpoint]);
                
                $apiKey = $_ENV['GEMINI_API_KEY']; // Read API key from environment
                
                // Log the request details for debugging (without sensitive data)
                $this->logger->info('Sending request to Gemini API', [
                    'model' => 'gemini-pro-vision',
                    'imageType' => $imageFile->getMimeType(),
                    'prompt' => $prompt
                ]);
                
                $response = $this->httpClient->request('POST', $endpoint, [
                    'query' => [
                        'key' => $apiKey
                    ],
                    'json' => [
                        'contents' => [
                            [
                                'parts' => [
                                    [
                                        'text' => $prompt
                                    ],
                                    [
                                        'inline_data' => [
                                            'mime_type' => $imageFile->getMimeType(),
                                            'data' => $imageData
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'generation_config' => [
                            'temperature' => 0.4,
                            'max_output_tokens' => 800
                        ]
                    ]
                ]);
                
                $statusCode = $response->getStatusCode();
                if ($statusCode === 200) {
                    $apiResponse = $response;
                    $this->logger->info('Successful endpoint found', ['endpoint' => $endpoint]);
                    break;
                } else {
                    $lastError = "Received status code {$statusCode} from {$endpoint}";
                    $this->logger->warning('API returned non-200 status', [
                        'endpoint' => $endpoint,
                        'status' => $statusCode,
                        'response' => $response->getContent(false)
                    ]);
                }
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                $this->logger->warning('Exception when trying endpoint', [
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // If no successful response was found
        if (!$apiResponse) {
            return new JsonResponse([
                'error' => "API Error: Could not connect to any Gemini endpoint. Last error: {$lastError}"
            ], Response::HTTP_BAD_GATEWAY);
        }
        
        $response = $apiResponse;
        
        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            $this->logger->error('Gemini API returned non-200 status code', [
                'status' => $statusCode,
                'response' => $response->getContent(false)
            ]);
            return new JsonResponse(['error' => "API Error: Received status code {$statusCode}"], Response::HTTP_BAD_GATEWAY);
        }
        
        $data = $response->toArray();
        
        // Add debugging output
        $this->logger->info('Gemini API Response', ['data' => $data]);
        
        // Extract and return the generated content from the new API structure
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $generatedContent = $data['candidates'][0]['content']['parts'][0]['text'];
            return new JsonResponse(['content' => $generatedContent]);
        } else {
            // Log the full response for debugging
            $this->logger->error('Unexpected Gemini API response structure', ['data' => $data]);
            return new JsonResponse(['error' => 'Could not generate content from image. Unexpected response format.'], Response::HTTP_BAD_REQUEST);
        }
    }
}
