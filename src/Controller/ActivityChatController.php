<?php

namespace App\Controller;

use App\Entity\Activities;
use App\Repository\ActivitiesRepository;
use App\Service\GeminiAIService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class ActivityChatController extends AbstractController
{
    private $geminiService;
    private $activitiesRepository;
    private $logger;
    
    /**
     * Test endpoint to verify basic JSON functionality
     * 
     * @Route("/client/activity-chat-test/{id}", name="app_activity_chat_test", methods={"GET", "POST"})
     */
    public function testChatEndpoint(int $id): JsonResponse
    {
        // Get environment information
        $env = $this->getParameter('kernel.environment');
        $debug = $this->getParameter('kernel.debug');
        $projectDir = $this->getParameter('kernel.project_dir');
        
        // Check if the gemini service is available
        $geminiAvailable = $this->geminiService !== null;
        
        return new JsonResponse([
            'status' => 'success',
            'message' => 'Test endpoint is working correctly',
            'activity_id' => $id,
            'timestamp' => time(),
            'debug_info' => [
                'environment' => $env,
                'debug_mode' => $debug,
                'project_dir' => $projectDir,
                'gemini_service_available' => $geminiAvailable
            ]
        ]);
    }

    public function __construct(
        ?GeminiAIService $geminiService = null,
        ActivitiesRepository $activitiesRepository,
        ?LoggerInterface $logger = null
    ) {
        $this->geminiService = $geminiService;
        $this->activitiesRepository = $activitiesRepository;
        $this->logger = $logger;
    }

    /**
     * Process a chat message about an activity
     *
     * @Route("/client/activity-chat/{id}", name="app_activity_chat", methods={"POST"})
     */
    public function chatAboutActivity(Request $request, int $id): JsonResponse
    {
        try {
            // Get the activity
            $activity = $this->activitiesRepository->find($id);
            
            if (!$activity) {
                return new JsonResponse(['error' => 'Activity not found'], 404);
            }
            
            // Get the user's question
            $data = json_decode($request->getContent(), true);
            $this->logger?->info('Received chat request data', ['data' => $data]);
            
            $question = $data['message'] ?? null;
            
            if (!$question) {
                return new JsonResponse(['error' => 'No question provided'], 400);
            }
            if ($this->geminiService) {
                // Get activity details to provide context
                $activityDetails = $this->prepareActivityContext($activity);
                
                // Create context for the AI
                $context = "You are an AI assistant specialized in providing information about this activity: \"{$activity->getActivityName()}\".\n\n";
                $context .= "Here are details about the activity:\n";
                $context .= $activityDetails;
                $context .= "\n\nOnly answer questions related to this specific activity based on the provided details. For questions outside this scope, politely indicate that you can only provide information about this particular activity.\n\n";
                $context .= "User Question: {$question}";
                
                // Log the request
                $this->logger?->info('Activity chat request', [
                    'activity_id' => $id,
                    'activity_name' => $activity->getActivityName(),
                    'question' => $question
                ]);
                
                // Since the image handling might be causing issues, let's simplify for now
                $image = null;
                // Use a default image instead of trying to read activity resources
                $mockImagePath = $this->getParameter('kernel.project_dir') . '/public/assets/img/activities/default.jpg';
                
                // Debug if the file exists
                if (!file_exists($mockImagePath)) {
                    $this->logger?->error('Default activity image not found at: ' . $mockImagePath);
                    // Try to find another image path that exists
                    $possiblePaths = [
                        $this->getParameter('kernel.project_dir') . '/public/assets/img/default.jpg',
                        $this->getParameter('kernel.project_dir') . '/public/assets/default.jpg',
                        $this->getParameter('kernel.project_dir') . '/public/default.jpg',
                    ];
                    
                    foreach ($possiblePaths as $path) {
                        if (file_exists($path)) {
                            $mockImagePath = $path;
                            $this->logger?->info('Found alternative image at: ' . $mockImagePath);
                            break;
                        }
                    }
                } else {
                    $this->logger?->info('Using default image at: ' . $mockImagePath);
                }
                
                try {
                    $image = new \Symfony\Component\HttpFoundation\File\UploadedFile(
                        $mockImagePath,
                        'activity_image.jpg',
                        'image/jpeg',
                        null,
                        true
                    );
                    $this->logger?->info('Created UploadedFile object successfully');
                } catch (\Exception $imageEx) {
                    $this->logger?->error('Error creating image: ' . $imageEx->getMessage());
                    // Continue without an image if there's an error
                    $image = null;
                }
                
                // Debug info before calling the API
                $this->logger?->info('About to call generateChatResponse', [
                    'context_length' => strlen($context),
                    'has_image' => $image ? 'yes' : 'no'
                ]);
                
                // Generate response using our new chat-specific method
                $response = null;
                try {
                    $response = $this->geminiService->generateChatResponse($context, $image);
                    $this->logger?->info('API response received', [
                        'success' => $response ? 'yes' : 'no',
                        'response_length' => $response ? strlen($response) : 0
                    ]);
                } catch (\Exception $apiEx) {
                    $this->logger?->error('API call failed: ' . $apiEx->getMessage(), [
                        'stack_trace' => $apiEx->getTraceAsString()
                    ]);
                    throw $apiEx; // Re-throw to be caught by the outer try-catch
                }
                
                if ($response) {
                    $jsonResponse = new JsonResponse(['response' => $response]);
                    $this->logger?->info('Returning successful JSON response');
                    return $jsonResponse;
                } else {
                    $this->logger?->warning('No response from API, using fallback');
                }
            } else {
                $this->logger?->warning('Gemini service not available');
            }
            
            // Fallback response if AI service is not available
            $fallbackResponse = new JsonResponse([
                'response' => "I'm sorry, but I can't provide information about this activity at the moment. Please try again later or contact customer support."
            ]);
            $this->logger?->info('Returning fallback response');
            return $fallbackResponse;
            
        } catch (\Exception $e) {
            $this->logger?->error('Error generating activity chat response', [
                'activity_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return new JsonResponse([
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Prepare activity details as context for the AI
     * 
     * @param Activities $activity The activity to extract context from
     * @return string The formatted context string
     */
    private function prepareActivityContext(Activities $activity): string
    {
        $context = "Name: {$activity->getActivityName()}\n";
        $context .= "Description: {$activity->getActivityDescription()}\n";
        $context .= "Location: {$activity->getActivityDestination()}\n";
        $context .= "Price: {$activity->getActivityPrice()} TND\n";
        
        if (method_exists($activity, 'getActivityDuration') && $activity->getActivityDuration()) {
            $context .= "Duration: {$activity->getActivityDuration()}\n";
        }
        
        if (method_exists($activity, 'getActivityGenre') && $activity->getActivityGenre()) {
            $context .= "Genre: {$activity->getActivityGenre()}\n";
        }
        
        return $context;
    }
}
