<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use App\Service\TotpService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TotpController extends AbstractController
{
    private $security;
    private $entityManager;
    private $usersRepository;
    private $totpService;
    private $session;
    private $eventDispatcher;

    public function __construct(
        Security $security,
        EntityManagerInterface $entityManager,
        UsersRepository $usersRepository,
        TotpService $totpService,
        SessionInterface $session,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->usersRepository = $usersRepository;
        $this->totpService = $totpService;
        $this->session = $session;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Show the TOTP setup page during registration
     * 
     * @Route("/register/totp-setup/{id}", name="app_totp_setup")
     */
    public function setupTotp(int $id): Response
    {
        // Check if there's a pending registration
        $pendingUserId = $this->session->get('pending_registration_id');
        if (!$pendingUserId || $pendingUserId != $id) {
            // If no pending registration or ID mismatch, redirect to register
            return $this->redirectToRoute('app_register');
        }
        
        $user = $this->usersRepository->find($id);
        
        if (!$user) {
            // If user not found, clear session and redirect to register
            $this->session->remove('pending_registration_id');
            $this->session->remove('registration_email');
            return $this->redirectToRoute('app_register');
        }
        
        // Ensure user is not yet enabled
        if ($user->isEnabled()) {
            // Account already active, redirect to login
            $this->session->remove('pending_registration_id');
            $this->session->remove('registration_email');
            
            $this->addFlash('info', 'Your account is already active. Please log in.');
            return $this->redirectToRoute('app_login');
        }
        
        // Generate a new TOTP secret if the user doesn't have one
        if (!$user->getTotpSecret()) {
            $secret = $this->totpService->generateSecret();
            $user->setTotpSecret($secret);
            $this->entityManager->flush();
        }
        
        // Generate QR code
        $qrCodeUri = $this->totpService->getQrCodeUri($user);
        
        return $this->render('security/totp_setup.html.twig', [
            'qr_code' => $qrCodeUri,
            'totp_secret' => $user->getTotpSecret(),
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'error' => null
        ]);
    }
    
    /**
     * Verify the TOTP code during setup
     * 
     * @Route("/register/totp-verify", name="app_totp_setup_verify", methods={"POST"})
     */
    public function verifySetupTotp(Request $request): Response
    {
        $userId = $request->request->get('user_id');
        $code = $request->request->get('totp_code');
        
        // Check if this matches our pending registration
        $pendingUserId = $this->session->get('pending_registration_id');
        if (!$pendingUserId || $pendingUserId != $userId) {
            // If no pending registration or ID mismatch, redirect to register
            return $this->redirectToRoute('app_register');
        }
        
        if (!$userId || !$code) {
            return $this->redirectToRoute('app_totp_setup', ['id' => $pendingUserId]);
        }
        
        $user = $this->usersRepository->find($userId);
        
        if (!$user) {
            $this->session->remove('pending_registration_id');
            $this->session->remove('registration_email');
            return $this->redirectToRoute('app_register');
        }
        
        // Verify TOTP code
        if ($this->totpService->verifyCode($user, $code)) {
            // Code is valid, activate the account
            $user->setTotpEnabled(true);
            $user->setEnabled(true);
            $this->entityManager->flush();
            
            // Clear the registration session
            $this->session->remove('pending_registration_id');
            $this->session->remove('registration_email');
            
            $this->addFlash('success', 'Your account has been activated successfully! You may now log in.');
            return $this->redirectToRoute('app_login');
        } else {
            // Invalid code, show error
            $this->addFlash('error', 'Invalid verification code. Please try again with the current code from your Google Authenticator app.');
            return $this->redirectToRoute('app_totp_setup', ['id' => $userId]);
        }
    }
    
    /**
     * Show the TOTP verification page during login
     * 
     * @Route("/login/totp-verify", name="app_totp_verify_page", methods={"GET"})
     */
    public function showVerifyTotp(Request $request): Response
    {
        // Get user ID from session
        $userId = $this->session->get('pending_login_user_id');
        
        if (!$userId) {
            $this->addFlash('error', 'Please log in first.');
            return $this->redirectToRoute('app_login');
        }
        
        $user = $this->usersRepository->find($userId);
        if (!$user) {
            $this->session->remove('pending_login_user_id');
            $this->addFlash('error', 'User not found. Please log in again.');
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('security/totp_verify.html.twig', [
            'user_id' => $userId,
            'email' => $user->getEmail(),
            'error' => $request->query->get('error')
        ]);
    }
    
    /**
     * Verify the TOTP code during login
     * 
     * @Route("/login/totp-verify", name="app_totp_verify", methods={"POST"})
     */
    public function verifyLoginTotp(Request $request): Response
    {
        // Log every request for debugging
        error_log('TOTP verification request received: ' . date('Y-m-d H:i:s'));
        error_log('Form data: ' . json_encode($request->request->all()));
        
        // Get form data
        $userId = $request->request->get('user_id');
        $code = $request->request->get('totp_code');
        
        error_log("User ID: $userId, Code length: " . strlen($code));
        error_log("Session user ID: " . $this->session->get('pending_login_user_id'));
        
        // Validate user ID from session
        $sessionUserId = $this->session->get('pending_login_user_id');
        if (!$sessionUserId || $sessionUserId != $userId) {
            error_log("Session mismatch: expected $sessionUserId, got $userId");
            $this->addFlash('error', 'Authentication session expired. Please log in again.');
            return $this->redirectToRoute('app_login');
        }
        
        // Validate code provided
        if (!$userId || !$code || strlen($code) != 6) {
            error_log("Invalid code format: " . $code);
            $this->addFlash('error', 'Please enter a valid 6-digit code.');
            return $this->redirectToRoute('app_totp_verify_page');
        }
        
        // Find user
        $user = $this->usersRepository->find($userId);
        if (!$user) {
            error_log("User not found: $userId");
            $this->session->remove('pending_login_user_id');
            $this->addFlash('error', 'User not found. Please try logging in again.');
            return $this->redirectToRoute('app_login');
        }
        
        error_log("Found user: " . $user->getEmail());
        
        // Verify the code
        try {
            if ($this->totpService->verifyCode($user, $code)) {
                error_log("Code is valid for user: " . $user->getEmail());
                
                // Clear pending login session
                $this->session->remove('pending_login_user_id');
                
                // Log the user in programmatically
                $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                $this->get('security.token_storage')->setToken($token);
                
                // Create the auth cookie by updating the session
                $this->session->set('_security_main', serialize($token));
                
                // Dispatch login event to update security context
                $event = new InteractiveLoginEvent($request, $token);
                $this->eventDispatcher->dispatch($event);
                
                // Log and add flash message
                error_log("User authenticated: " . $user->getEmail());
                $this->addFlash('success', 'Login successful! Welcome back, ' . $user->getName());
                
                // Determine redirect based on role
                $role = $user->getRole();
                
                if ($role === 'admin') {
                    error_log("Redirecting admin to dashboard");
                    return $this->redirectToRoute('admin_dashboard');
                } elseif ($role === 'Publicitaire') {
                    error_log("Redirecting publicator to dashboard");
                    return $this->redirectToRoute('app_publicator');
                } else {
                    error_log("Redirecting regular user to home page");
                    return $this->redirectToRoute('app_home');
                }
                
            } else {
                error_log("Invalid code for user: " . $user->getEmail());
                $this->addFlash('error', 'Invalid verification code. Please try again with the current code from your Google Authenticator app.');
                return $this->redirectToRoute('app_totp_verify_page');
            }
        } catch (\Exception $e) {
            error_log("Exception during verification: " . $e->getMessage());
            error_log($e->getTraceAsString());
            $this->addFlash('error', 'Error verifying code. Please try again.');
            return $this->redirectToRoute('app_totp_verify_page');
        }
    }
}
