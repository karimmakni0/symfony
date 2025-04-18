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

class TotpController extends AbstractController
{
    private $security;
    private $entityManager;
    private $usersRepository;
    private $totpService;
    private $session;

    public function __construct(
        Security $security,
        EntityManagerInterface $entityManager,
        UsersRepository $usersRepository,
        TotpService $totpService,
        SessionInterface $session
    ) {
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->usersRepository = $usersRepository;
        $this->totpService = $totpService;
        $this->session = $session;
    }

    /**
     * Show the TOTP setup page during registration
     * 
     * @Route("/register/totp-setup/{id}", name="app_totp_setup")
     */
    public function setupTotp(int $id): Response
    {
        $user = $this->usersRepository->find($id);
        
        if (!$user) {
            throw $this->createNotFoundException('User not found');
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
        
        if (!$userId || !$code) {
            return $this->redirectToRoute('app_login');
        }
        
        $user = $this->usersRepository->find($userId);
        
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        
        // Verify the TOTP code
        if ($this->totpService->verifyCode($user, $code)) {
            // Enable TOTP for the user
            $user->setTotpEnabled(true);
            $this->entityManager->flush();
            
            // Add a flash message
            $this->addFlash('success', 'Two-factor authentication set up successfully. You can now log in.');
            
            // Redirect to login
            return $this->redirectToRoute('app_login');
        }
        
        // Invalid code, show error
        return $this->render('security/totp_setup.html.twig', [
            'qr_code' => $this->totpService->getQrCodeUri($user),
            'totp_secret' => $user->getTotpSecret(),
            'user_id' => $user->getId(),
            'error' => 'Invalid verification code. Please try again.'
        ]);
    }
    
    /**
     * Show the TOTP verification page during login
     * 
     * @Route("/login/totp-verify", name="app_totp_verify_page")
     */
    public function showVerifyTotp(Request $request): Response
    {
        // Get user ID from session
        $userId = $this->session->get('totp_user_id');
        
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('security/totp_verify.html.twig', [
            'user_id' => $userId,
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
        $userId = $request->request->get('user_id');
        $code = $request->request->get('totp_code');
        
        if (!$userId || !$code) {
            return $this->redirectToRoute('app_login');
        }
        
        $user = $this->usersRepository->find($userId);
        
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        
        // Verify the TOTP code
        if ($this->totpService->verifyCode($user, $code)) {
            // Clear session
            $this->session->remove('totp_user_id');
            
            // Login was successful, manually authenticate user
            // Since we can't use the regular security system for this custom 2FA flow,
            // we'll store a flag in the session
            $this->session->set('2fa_verified', true);
            $this->session->set('2fa_user_id', $user->getId());
            
            // Redirect to login check route which will handle the proper redirection
            return $this->redirectToRoute('login_check');
        }
        
        // Invalid code, show error
        return $this->redirectToRoute('app_totp_verify_page', [
            'error' => 'Invalid verification code. Please try again.'
        ]);
    }
}
