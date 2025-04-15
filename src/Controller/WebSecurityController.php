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

class WebSecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, UsersRepository $usersRepository, UserPasswordHasherInterface $passwordHasher): Response
    {
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
                    // Login successful, manually authenticate the user
                    // Check role and redirect
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
    public function loginCheck()
    {
        // Get current user
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
