<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class WebSecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, UsersRepository $usersRepository, UserPasswordHasherInterface $passwordHasher, SessionInterface $session): Response
    {
        // Check if 2FA is verified
        if ($session->has('2fa_verified') && $session->get('2fa_verified') === true) {
            $userId = $session->get('2fa_user_id');
            $user = $usersRepository->find($userId);
            
            if ($user) {
                // Clear session
                $session->remove('2fa_verified');
                $session->remove('2fa_user_id');
                
                // Login successful, redirect based on role
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
        
        // If user is already logged in, redirect to appropriate dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('login_check');
        }
        
        // Custom error messages
        $customError = null;
        $lastUsername = '';
        
        // Handle form submission manually
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $lastUsername = $email;
            
            // Find user by email
            $user = $usersRepository->findOneBy(['email' => $email]);
            
            if (!$user) {
                $customError = 'Email not found. Please create an account.';
            } else {
                // Check password
                $isPasswordValid = $passwordHasher->isPasswordValid($user, $password);
                
                if (!$isPasswordValid) {
                    $customError = 'Wrong password. Please try again.';
                } else {
                    // Check if 2FA is enabled for this user
                    if ($user->isTotpAuthenticationEnabled()) {
                        // Store user ID in session for 2FA verification
                        $session->set('totp_user_id', $user->getId());
                        
                        // Redirect to 2FA verification page
                        return $this->redirectToRoute('app_totp_verify_page');
                    }
                    
                    // No 2FA, login successful
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
        }
        
        // Get last error from authentication utils if applicable
        $error = $authenticationUtils->getLastAuthenticationError();
        
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'custom_error' => $customError,
        ]);
    }

    #[Route('/login_check', name: 'login_check')]
    public function loginCheck(SessionInterface $session)
    {
        // Check if coming from 2FA verification
        if ($session->has('2fa_verified') && $session->get('2fa_verified') === true) {
            $userId = $session->get('2fa_user_id');
            $user = $this->getDoctrine()->getRepository(Users::class)->find($userId);
            
            if ($user) {
                // Clear session
                $session->remove('2fa_verified');
                $session->remove('2fa_user_id');
                
                // Login successful, redirect based on role
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
        
        // Regular login check
        $user = $this->getUser();

        if (!$user) {
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
}
