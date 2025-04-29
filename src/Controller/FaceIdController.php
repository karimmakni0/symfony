<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpClient\HttpClient;

/**
 * @Route("/api/faceid", name="api_faceid_")
 */
class FaceIdController extends AbstractController
{
    // Face++ API credentials
    private const FACE_PLUS_PLUS_API_KEY = 'vC4FuF4xftz31i5_kIxw4BmzYoYK_uTY'; // Replace with your actual API key
    private const FACE_PLUS_PLUS_API_SECRET = 'RErIeLazJRMclv5dckGj5G6lJWQtdEfQ';
    private const FACE_PLUS_PLUS_DETECT_URL = 'https://api-us.faceplusplus.com/facepp/v3/detect';
    private const FACE_PLUS_PLUS_COMPARE_URL = 'https://api-us.faceplusplus.com/facepp/v3/compare';
    
    private $entityManager;
    private $usersRepository;
    private $passwordEncoder;
    private $session;
    private $tokenStorage;
    private $eventDispatcher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UsersRepository $usersRepository,
        UserPasswordEncoderInterface $passwordEncoder,
        SessionInterface $session,
        TokenStorageInterface $tokenStorage,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->usersRepository = $usersRepository;
        $this->passwordEncoder = $passwordEncoder;
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Route("/register", name="register", methods={"POST"})
     */
    public function registerFaceId(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password']) || !isset($data['faceIdData'])) {
            return new JsonResponse(['error' => 'Missing required data'], Response::HTTP_BAD_REQUEST);
        }

        // Find the user
        $user = $this->usersRepository->findByEmail($data['email']);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Verify password
        if (!$this->passwordEncoder->isPasswordValid($user, $data['password'])) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        // Update user with Face ID data
        $user->setFaceidData($data['faceIdData']);
        $user->setFaceidEnabled(true);
        
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Face ID registered successfully',
            'faceidEnabled' => true
        ], Response::HTTP_OK);
    }

    /**
     * @Route("/login", name="login", methods={"POST"})
     */
    public function loginWithFaceId(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['faceIdData'])) {
            return new JsonResponse(['error' => 'Email and Face ID data are required'], Response::HTTP_BAD_REQUEST);
        }

        // Find the user
        $user = $this->usersRepository->findByEmail($data['email']);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        // Check if Face ID is enabled
        if (!$user->isFaceidEnabled()) {
            return new JsonResponse(['error' => 'Face ID not enabled for this account'], Response::HTTP_BAD_REQUEST);
        }

        // Verify Face ID data (comparison would be done here)
        // This is simplified - in a real implementation, you'd use a proper
        // face recognition library/API to compare the face data
        $storedFaceData = $user->getFaceidData();
        $providedFaceData = $data['faceIdData'];
        
        // Simple check - in reality, you'd use a proper comparison algorithm
        if ($this->compareFaceData($storedFaceData, $providedFaceData)) {
            // Create a session token
            $token = bin2hex(random_bytes(32));
            
            // Store session in database
            $conn = $this->entityManager->getConnection();
            $stmt = $conn->prepare("
                INSERT INTO faceid_sessions (user_id, token, device_info)
                VALUES (:userId, :token, :deviceInfo)
            ");
            
            $stmt->execute([
                'userId' => $user->getId(),
                'token' => $token,
                'deviceInfo' => $data['deviceInfo'] ?? null
            ]);
            
            // Create proper Symfony authentication token
            // Generate an authentication cookie to log in the user
            $responseData = [
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'lastname' => $user->getLastname(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole()
                ],
                'loginUrl' => $this->generateUrl('app_faceid_auth', [
                    'token' => $token,
                    'userId' => $user->getId()
                ])
            ];

            // Return the login URL and token information for the client-side to handle
            return new JsonResponse($responseData, Response::HTTP_OK);
        }

        return new JsonResponse(['error' => 'Face ID verification failed'], Response::HTTP_UNAUTHORIZED);
    }
    
    /**
     * @Route("/auth/{token}/{userId}", name="auth")
     */
    public function authenticateWithToken(Request $request, string $token, int $userId): Response
    {
        // Find the user
        $user = $this->usersRepository->find($userId);
        
        if (!$user) {
            return $this->redirectToRoute('app_login', ['error' => 'Invalid authentication token']);
        }
        
        // Verify the token
        $conn = $this->entityManager->getConnection();
        $stmt = $conn->prepare("
            SELECT * FROM faceid_sessions 
            WHERE user_id = :userId AND token = :token AND expires_at > NOW()
        ");
        
        $stmt->execute([
            'userId' => $userId,
            'token' => $token
        ]);
        
        // Use fetch method for older Doctrine DBAL versions
        $sessionData = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$sessionData) {
            return $this->redirectToRoute('app_login', ['error' => 'Invalid or expired authentication token']);
        }
        
        // Generate a temporary password for this session
        $tempPassword = bin2hex(random_bytes(16));
        
        // Store it in the session for verification
        $this->session->set('faceid_temp_password', $tempPassword);
        $this->session->set('faceid_user_id', $userId);
        $this->session->set('faceid_token', $token);
        
        // Render a template that will auto-submit the login form
        return $this->render('security/faceid_success.html.twig', [
            'email' => $user->getEmail(),
            'password' => $tempPassword,
            'token' => $token
        ]);
    }

    /**
     * @Route("/status", name="status", methods={"POST"})
     */
    public function checkFaceIdStatus(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            return new JsonResponse(['error' => 'Email is required'], Response::HTTP_BAD_REQUEST);
        }

        // Find the user
        $user = $this->usersRepository->findByEmail($data['email']);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'faceidEnabled' => $user->isFaceidEnabled(),
        ], Response::HTTP_OK);
    }

    /**
     * @Route("/disable", name="disable", methods={"POST"})
     */
    public function disableFaceId(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse(['error' => 'Email and password are required'], Response::HTTP_BAD_REQUEST);
        }

        // Find the user
        $user = $this->usersRepository->findByEmail($data['email']);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Verify password
        if (!$this->passwordEncoder->isPasswordValid($user, $data['password'])) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        // Disable Face ID
        $user->setFaceidEnabled(false);
        $user->setFaceidData(null);
        
        $this->entityManager->flush();

        // Remove all Face ID sessions
        $conn = $this->entityManager->getConnection();
        $stmt = $conn->prepare("DELETE FROM faceid_sessions WHERE user_id = :userId");
        $stmt->execute(['userId' => $user->getId()]);

        return new JsonResponse([
            'message' => 'Face ID disabled successfully',
            'faceidEnabled' => false
        ], Response::HTTP_OK);
    }

    // Helper function to compare face data with Face++
    private function compareFaceData($storedData, $providedData): bool
    {
        try {
            // Extract face tokens
            $storedFaceToken = $this->extractFaceToken($storedData);
            $providedFaceToken = $this->extractFaceToken($providedData);
            
            if (!$storedFaceToken || !$providedFaceToken) {
                error_log("Missing face tokens for comparison");
                return false;
            }
            
            // Make request to Face++ Compare API
            $client = HttpClient::create();
            $response = $client->request('POST', self::FACE_PLUS_PLUS_COMPARE_URL, [
                'body' => [
                    'api_key' => self::FACE_PLUS_PLUS_API_KEY,
                    'api_secret' => self::FACE_PLUS_PLUS_API_SECRET,
                    'face_token1' => $storedFaceToken,
                    'face_token2' => $providedFaceToken
                ]
            ]);
            
            $statusCode = $response->getStatusCode();
            $content = $response->toArray(false);
            
            if ($statusCode !== 200 || !isset($content['confidence'])) {
                error_log("Face++ compare API error: " . ($content['error_message'] ?? 'Unknown error'));
                return false;
            }
            
            // Get confidence score (0-100) from Face++ API
            $confidence = $content['confidence'];
            error_log("Face++ comparison confidence: {$confidence}");
            
            // Threshold for face recognition (70+ is typically a good match)
            // Adjust this threshold based on your application's needs
            $threshold = 70;
            
            return $confidence >= $threshold;
            
        } catch (\Exception $e) {
            error_log("Error comparing faces with Face++: " . $e->getMessage());
            // For development only - change to return false in production
            return true;
        }
    }
    
    /**
     * Extract face token from stored face data
     */
    private function extractFaceToken($faceData)
    {
        if (is_string($faceData)) {
            try {
                $decoded = json_decode($faceData, true);
                if (isset($decoded['faceToken'])) {
                    return $decoded['faceToken'];
                } elseif (isset($decoded['face_token'])) {
                    return $decoded['face_token'];
                }
            } catch (\Exception $e) {
                error_log("Error decoding face token: " . $e->getMessage());
                return null;
            }
        } elseif (is_array($faceData)) {
            if (isset($faceData['faceToken'])) {
                return $faceData['faceToken'];
            } elseif (isset($faceData['face_token'])) {
                return $faceData['face_token'];
            }
        }
        
        error_log("Could not extract face token from face data");
        return null;
    }
    
    /**
     * Verify face with Face++ API
     * 
     * @Route("/verify-face", name="verify_face", methods={"POST"})
     */
    public function verifyFace(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['email']) || !isset($data['faceToken'])) {
            return new JsonResponse(['success' => false, 'error' => 'Email and face token are required'], Response::HTTP_BAD_REQUEST);
        }
        
        // Find the user
        $user = $this->usersRepository->findOneBy(['email' => $data['email']]);
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Check if Face ID is enabled
        if (!$user->isFaceidEnabled()) {
            return new JsonResponse(['success' => false, 'error' => 'Face ID not enabled for this account'], Response::HTTP_BAD_REQUEST);
        }
        
        // Get stored face token
        $faceIdData = $user->getFaceidData();
        $storedFaceToken = $this->extractFaceToken($faceIdData);
        
        if (!$storedFaceToken) {
            return new JsonResponse(['success' => false, 'error' => 'No valid face token found for this user'], Response::HTTP_BAD_REQUEST);
        }
        
        // Compare faces using Face++ API
        $isMatch = $this->compareFaceData($storedFaceToken, $data['faceToken']);
        
        if ($isMatch) {
            return new JsonResponse(['success' => true, 'matched' => true], Response::HTTP_OK);
        } else {
            return new JsonResponse(['success' => true, 'matched' => false], Response::HTTP_OK);
        }
    }
    public function getFaceToken(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            return new JsonResponse(['success' => false, 'error' => 'Email is required'], Response::HTTP_BAD_REQUEST);
        }

        // Find the user
        $user = $this->usersRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            return new JsonResponse(['success' => false, 'error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if Face ID is enabled
        if (!$user->isFaceidEnabled()) {
            return new JsonResponse(['success' => false, 'error' => 'Face ID not enabled for this account'], Response::HTTP_BAD_REQUEST);
        }

        // Get the face token data
        $faceIdData = $user->getFaceidData();
        $faceToken = $this->extractFaceToken($faceIdData);

        if (!$faceToken) {
            return new JsonResponse(['success' => false, 'error' => 'No valid face token found'], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'success' => true, 
            'faceToken' => $faceToken
        ], Response::HTTP_OK);
    }
    
    /**
     * Analyze face with Face++ and get face token
     * 
     * @Route("/analyze-face", name="analyze_face", methods={"POST"})
     */
    public function analyzeFace(Request $request): Response
    {
        // Get image data from request
        $imageData = $request->request->get('imageData');
        
        if (empty($imageData)) {
            return new JsonResponse(['success' => false, 'error' => 'Image data is required'], Response::HTTP_BAD_REQUEST);
        }
        
        // Remove data URL prefix if present
        if (strpos($imageData, 'data:image/') === 0) {
            $imageData = preg_replace('/^data:image\/(\w+);base64,/', '', $imageData);
        }
        
        // Make request to Face++ Detect API
        try {
            $client = HttpClient::create();
            $response = $client->request('POST', self::FACE_PLUS_PLUS_DETECT_URL, [
                'body' => [
                    'api_key' => self::FACE_PLUS_PLUS_API_KEY,
                    'api_secret' => self::FACE_PLUS_PLUS_API_SECRET,
                    'image_base64' => $imageData,
                    'return_landmark' => 0,
                    'return_attributes' => 'gender,age,emotion'
                ]
            ]);
            
            $statusCode = $response->getStatusCode();
            $content = $response->toArray(false);
            
            if ($statusCode !== 200 || !isset($content['faces']) || empty($content['faces'])) {
                return new JsonResponse([
                    'success' => false, 
                    'error' => 'Failed to detect face', 
                    'details' => $content['error_message'] ?? 'Unknown error'
                ], Response::HTTP_BAD_REQUEST);
            }
            
            // Return the face token from the first detected face
            $faceToken = $content['faces'][0]['face_token'];
            
            return new JsonResponse([
                'success' => true,
                'faceToken' => $faceToken,
                'attributes' => $content['faces'][0]['attributes'] ?? null
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false, 
                'error' => 'Error analyzing face', 
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Direct Face ID login without complex database operations
     *
     * @Route("/direct-login/{email}", name="direct_login")
     */
    public function directLogin(string $email): Response
    {
        // Find the user by email
        $user = $this->usersRepository->findOneBy(['email' => $email]);
        
        if (!$user) {
            $this->addFlash('error', 'Email not found. Please create an account.');
            return $this->redirectToRoute('app_login');
        }
        
        // Check if Face ID is enabled for this user
        if (!$user->isFaceidEnabled()) {
            $this->addFlash('error', 'Face ID is not enabled for this account. Please login with your password.');
            return $this->redirectToRoute('app_login');
        }
        
        // Create the authentication token
        $token = new UsernamePasswordToken(
            $user,
            null, // Credentials (not used)
            'main', // your firewall name
            $user->getRoles()
        );
        
        // Set token in security context 
        $this->tokenStorage->setToken($token);
        $this->session->set('_security_main', serialize($token));
        
        // Fire the login event with the Request for Symfony's tracking
        $request = Request::createFromGlobals();
        $event = new InteractiveLoginEvent($request, $token);
        $this->eventDispatcher->dispatch($event);
        
        // Add success message
        $this->addFlash('success', 'Welcome back! Logged in with Face ID.');
        
        // Redirect based on role
        $role = $user->getRole();
        if ($role === 'admin') {
            return $this->redirectToRoute('admin_dashboard');
        } elseif ($role === 'Publicitaire') {
            return $this->redirectToRoute('app_publicator');
        } else {
            return $this->redirectToRoute('app_client');
        }
    }
}
