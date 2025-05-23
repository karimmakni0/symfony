<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use App\Service\DeviceDetectionService;
use App\Service\HCaptchaService;
use App\Service\LoginNotificationService;
use App\Service\TotpService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class WebSecurityController extends AbstractController
{
    private $usersRepository;
    private $entityManager;
    private $session;
    private $passwordHasher;
    private $tokenStorage;
    private $totpService;
    private $hCaptchaService;
    private $loginNotificationService;
    private $deviceDetectionService;

    public function __construct(
        UsersRepository $usersRepository,
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        UserPasswordHasherInterface $passwordHasher,
        TokenStorageInterface $tokenStorage,
        TotpService $totpService,
        HCaptchaService $hCaptchaService,
        LoginNotificationService $loginNotificationService,
        DeviceDetectionService $deviceDetectionService
    ) {
        $this->usersRepository = $usersRepository;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->passwordHasher = $passwordHasher;
        $this->tokenStorage = $tokenStorage;
        $this->totpService = $totpService;
        $this->hCaptchaService = $hCaptchaService;
        $this->loginNotificationService = $loginNotificationService;
        $this->deviceDetectionService = $deviceDetectionService;
    }
    
    #[Route('/login', name: 'app_login')]
    public function login(Request $request): Response
    {
        // If user is already logged in, redirect to appropriate dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('login_check');
        }
        
        // Check for password reset parameter
        if ($request->query->get('password_reset') === 'success') {
            $this->addFlash('success', 'Your password has been successfully reset. You can now log in with your new password.');
        }
        
        // Custom error messages
        $customError = null;
        $lastUsername = '';
        
        // Handle form submission manually
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $lastUsername = $email;
            
            // Check if this is a Face ID authentication request
            $faceidLogin = $request->request->get('faceid_login');
            
            // Skip hCaptcha verification for Face ID login
            if (!$faceidLogin && $password) {
                // Only verify hCaptcha for regular password login
                $hCaptchaResponse = $request->request->get('h-captcha-response');
                $clientIp = $request->getClientIp();
                
                if (!$this->hCaptchaService->verify($hCaptchaResponse, $clientIp)) {
                    $customError = 'Please verify that you are not a robot.';
                }
            }
            
            // Find user by email
            $user = $this->usersRepository->findOneBy(['email' => $email]);
            
            if (!$user) {
                $customError = 'Email not found. Please create an account.';
            } elseif (!$user->isEnabled()) {
                $customError = 'Your account is not yet activated. Please complete the registration process.';
            } else {
                // Process authentication based on login type
                if ($faceidLogin === 'true') {
                    // FACE ID LOGIN FLOW
                    if (!$user->isFaceidEnabled()) {
                        $customError = 'Face ID is not enabled for this account. Please login with your password.';
                    } else {
                        // Create authentication token
                        $token = new UsernamePasswordToken(
                            $user,
                            null,
                            'main',
                            $user->getRoles()
                        );
                        
                        // Set security token
                        $this->tokenStorage->setToken($token);
                        $this->session->set('_security_main', serialize($token));
                        
                        // Success message
                        $this->addFlash('success', 'Welcome back! Logged in with Face ID.');
                        
                        // Redirect based on role
                        $role = $user->getRole();
                        if ($role === 'admin') {
                            return $this->redirectToRoute('admin_dashboard');
                        } elseif ($role === 'Publicitaire') {
                            return $this->redirectToRoute('app_publicator');
                        } else {
                            return $this->redirectToRoute('app_home');
                        }
                    }
                } elseif ($password) {
                    // PASSWORD LOGIN FLOW
                    $isPasswordValid = $this->passwordHasher->isPasswordValid($user, $password);
                    
                    if ($isPasswordValid) {
                        // Password is valid, save user ID for 2FA verification
                        $this->session->set('pending_login_user_id', $user->getId());
                        $this->session->set('pending_login_remember_me', $request->request->get('_remember_me', false));
                        
                        // Record login attempt (doesn't send notification until after 2FA)
                        $this->session->set('check_new_device', true);
                        
                        // Redirect to 2FA verification page
                        return $this->redirectToRoute('app_totp_verify_page');
                    } else {
                        $customError = 'Wrong password. Please try again.';
                    }
                } else {
                    // NEITHER FACE ID NOR PASSWORD PROVIDED
                    $customError = 'Password is required';
                }
            }
        }
        
        // Display login form
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => null,
            'custom_error' => $customError,
        ]);
    }

    #[Route('/login_check', name: 'login_check')]
    public function loginCheck(): Response
    {
        // Regular login check
        $user = $this->getUser();

        if (!$user) {
            // If no user, redirect to login page
            $this->addFlash('error', 'You need to log in first.');
            return $this->redirectToRoute('app_login');
        }

        // Check user role and redirect appropriately
        $role = $user->getRole();

        if ($role === 'admin') {
            return $this->redirectToRoute('admin_dashboard');
        } elseif ($role === 'Publicitaire') {
            return $this->redirectToRoute('app_publicator');
        } else {
            return $this->redirectToRoute('app_home');
        }
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method can be empty - it will be intercepted by the logout key on your firewall
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
    
    /**
     * Direct handler for the old Face ID login URL to catch any cached redirects
     * 
     * @Route("/direct-faceid-login/{email}", name="direct_faceid_login_fallback")
     */
    public function directFaceIdLogin(string $email): Response
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
        
        // Create authentication token
        $token = new UsernamePasswordToken(
            $user,
            null,
            'main',
            $user->getRoles()
        );
        
        // Set security token
        $this->tokenStorage->setToken($token);
        $this->session->set('_security_main', serialize($token));
        
        // Success message
        $this->addFlash('success', 'Welcome back! Logged in with Face ID.');
        
        // Redirect based on role
        $role = $user->getRole();
        if ($role === 'admin') {
            return $this->redirectToRoute('admin_dashboard');
        } elseif ($role === 'Publicitaire') {
            return $this->redirectToRoute('app_publicator');
        } else {
            return $this->redirectToRoute('app_home');
        }
    }
}
