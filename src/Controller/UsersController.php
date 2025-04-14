<?php

namespace App\Controller;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UsersController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        // Create a new user object
        $user = new Users();
        
        // Process form submission
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $lastname = $request->request->get('lastname');
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');
            
            // Basic validation
            $errors = [];
            
            if (empty($name)) {
                $errors[] = 'First name is required';
            }
            
            if (empty($lastname)) {
                $errors[] = 'Last name is required';
            }
            
            if (empty($email)) {
                $errors[] = 'Email is required';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email is not valid';
            }
            
            if (empty($password)) {
                $errors[] = 'Password is required';
            }
            
            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match';
            }
            
            // Check if email already exists
            $existingUser = $entityManager->getRepository(Users::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $errors[] = 'Email already exists';
            }
            
            // If no errors, create the user
            if (empty($errors)) {
                $user->setName($name);
                $user->setLastname($lastname);
                $user->setEmail($email);
                
                // Set default role as "user"
                $user->setRole('user');
                
                // Hash the password
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
                
                // Save the user to database
                $entityManager->persist($user);
                $entityManager->flush();
                
                // Add a flash message
                $this->addFlash('success', 'Registration successful! You can now log in.');
                
                // Redirect to login page
                return $this->redirectToRoute('app_login');
            }
            
            // If there are errors, display them
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }
        
        return $this->render('security/register.html.twig');
    }
}
