<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Google;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class GoogleController extends AbstractController
{
    private $entityManager;
    private $usersRepository;
    private $passwordEncoder;
    private $urlGenerator;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        UsersRepository $usersRepository,
        UserPasswordEncoderInterface $passwordEncoder,
        UrlGeneratorInterface $urlGenerator,
        LoggerInterface $logger = null
    ) {
        $this->entityManager = $entityManager;
        $this->usersRepository = $usersRepository;
        $this->passwordEncoder = $passwordEncoder;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
    }

    /**
     * Link to this controller to start the "connect" process
     *
     * @Route("/connect/google", name="connect_google")
     */
    public function connectAction(): RedirectResponse
    {
        // Create the Google provider
        $provider = $this->getGoogleClient();

        // Generate URL for request to Google's OAuth server
        $authUrl = $provider->getAuthorizationUrl([
            'scope' => ['email', 'profile'],
        ]);

        // Store state in session for CSRF protection
        $this->get('session')->set('oauth2state', $provider->getState());

        // Redirect to Google
        return new RedirectResponse($authUrl);
    }

    /**
     * Google redirects to this route after authentication
     *
     * @Route("/connect/google/check", name="connect_google_check")
     */
    public function connectCheckAction(Request $request): Response
    {
        if (!$request->query->has('code')) {
            return $this->redirectToRoute('app_login');
        }

        // CSRF check
        if ($request->query->has('state') && 
            $this->get('session')->has('oauth2state') && 
            $request->query->get('state') !== $this->get('session')->get('oauth2state')
        ) {
            $this->get('session')->remove('oauth2state');
            return $this->render('security/login.html.twig', [
                'custom_error' => 'Invalid state parameter, authentication failed.',
                'last_username' => ''
            ]);
        }

        try {
            // Create Google client
            $provider = $this->getGoogleClient();

            // Get access token
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $request->query->get('code')
            ]);

            // Get resource owner (user data)
            $user = $provider->getResourceOwner($token);
            $userData = $user->toArray();

            // Check if user exists by email
            $existingUser = $this->usersRepository->findByEmail($userData['email']);

            if ($existingUser) {
                // Log in the existing user
                return $this->loginUser($existingUser);
            } else {
                // Create a new user
                return $this->registerGoogleUser($userData);
            }
        } catch (IdentityProviderException $e) {
            // Failed to get access token or user details
            return $this->render('security/login.html.twig', [
                'custom_error' => 'Authentication failed: ' . $e->getMessage(),
                'last_username' => ''
            ]);
        }
    }

    /**
     * Register a new user from Google data
     */
    private function registerGoogleUser(array $userData): Response
    {
        $user = new Users();
        
        // Set user data from Google
        $user->setEmail($userData['email']);
        
        // Split full name into first and last name if available
        if (isset($userData['name'])) {
            $nameParts = explode(' ', $userData['name']);
            $user->setName($nameParts[0] ?? '');
            $user->setLastname(implode(' ', array_slice($nameParts, 1)) ?? '');
        } else {
            // Fallbacks if name is not available
            $user->setName($userData['given_name'] ?? 'Google');
            $user->setLastname($userData['family_name'] ?? 'User');
        }
        
        // Set user role and make account active
        $user->setRole('user');
        $user->setEnabled(true);
        
        // Set a secure random password
        $randomPassword = bin2hex(random_bytes(16));
        $encodedPassword = $this->passwordEncoder->encodePassword($user, $randomPassword);
        $user->setPassword($encodedPassword);
        
        // Set profile image if available
        if (isset($userData['picture'])) {
            // Store Google profile URLs with a special prefix to indicate it's an external URL
            $user->setImage('google_profile:' . $userData['picture']);
        }
        
        // Save user
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        // Login the new user
        return $this->loginUser($user);
    }

    /**
     * Login a user and redirect to homepage
     */
    private function loginUser(Users $user): Response
    {
        // Create the authentication token
        $token = new UsernamePasswordToken(
            $user,
            null,
            'main',
            $user->getRoles()
        );
        
        // Set token in security token storage
        $this->get('security.token_storage')->setToken($token);
        
        // Update the session
        $request = $this->get('request_stack')->getCurrentRequest();
        if ($request && $request->hasSession()) {
            $request->getSession()->set('_security_main', serialize($token));
        }
        
        // Redirect to homepage
        return $this->redirectToRoute('app_home');
    }

    /**
     * Create Google client
     */
    private function getGoogleClient(): Google
    {
        try {
            // Generate the absolute URL for the redirect
            $redirectUri = $this->urlGenerator->generate('connect_google_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
            
            // Log the redirect URI for debugging
            if ($this->logger) {
                $this->logger->info('Google OAuth redirect URI: ' . $redirectUri);
            }
            
            // Check if client secret is configured
            $clientSecret = $this->getParameter('google_client_secret');
            if ($this->logger) {
                $this->logger->info('Google OAuth client secret check', [
                    'length' => strlen($clientSecret),
                    'is_client_id' => strpos($clientSecret, '.apps.googleusercontent.com') !== false,
                ]);
            }
            
            if (empty($clientSecret) || $clientSecret === '%env(GOOGLE_CLIENT_SECRET)%') {
                throw new \Exception('Google client secret not configured. Please set GOOGLE_CLIENT_SECRET in .env.local');
            }
            
            if (strpos($clientSecret, '.apps.googleusercontent.com') !== false) {
                throw new \Exception('You entered your client ID as the client secret. Please get the actual client secret from Google Cloud Console.');
            }
            
            // Note: You MUST register this exact redirect URI in your Google Cloud Console
            $googleConfig = [
                'clientId'     => $this->getParameter('google_client_id'),
                'clientSecret' => $clientSecret,
                'redirectUri'  => $redirectUri,
            ];
            
            // Log the config for debugging (without exposing the secret)
            if ($this->logger) {
                $this->logger->info('Google OAuth config', [
                    'clientId' => $googleConfig['clientId'],
                    'redirectUri' => $googleConfig['redirectUri']
                ]);
            }
            
            return new Google($googleConfig);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Failed to create Google client: ' . $e->getMessage());
            }
            throw $e;
        }
    }
}
