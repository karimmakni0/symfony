<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use App\Service\TotpService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    private $entityManager;
    private $usersRepository;
    private $passwordHasher;
    private $totpService;

    public function __construct(
        EntityManagerInterface $entityManager,
        UsersRepository $usersRepository,
        UserPasswordHasherInterface $passwordHasher,
        TotpService $totpService
    ) {
        $this->entityManager = $entityManager;
        $this->usersRepository = $usersRepository;
        $this->passwordHasher = $passwordHasher;
        $this->totpService = $totpService;
    }

    /**
     * @Route("/register", name="app_register", methods={"GET", "POST"})
     */
    public function register(Request $request): Response
    {
        // If user is logged in, redirect
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // Display the registration form for GET request
        if ($request->isMethod('GET')) {
            return $this->render('security/register.html.twig');
        }

        // Process form submission for POST request
        $name = $request->request->get('name');
        $lastname = $request->request->get('lastname');
        $email = $request->request->get('email');
        $phone = $request->request->get('phone');
        $birthday = $request->request->get('birthday');
        $gender = $request->request->get('gender');
        $password = $request->request->get('password');
        $confirmPassword = $request->request->get('confirm_password');

        // Validation
        if (!$password || strlen($password) < 6) {
            $this->addFlash('error', 'Password must be at least 6 characters long');
            return $this->redirectToRoute('app_register');
        }

        if ($password !== $confirmPassword) {
            $this->addFlash('error', 'Passwords do not match');
            return $this->redirectToRoute('app_register');
        }

        // Check if email already exists
        $existingUser = $this->usersRepository->findOneBy(['email' => $email]);
        if ($existingUser) {
            $this->addFlash('error', 'Email already registered');
            return $this->redirectToRoute('app_register');
        }

        try {
            // Create new user
            $user = new Users();
            $user->setName($name);
            $user->setLastname($lastname);
            $user->setEmail($email);
            $user->setPhone($phone);
            $user->setGender($gender);
            
            // Process birthday if provided
            if (!empty($birthday)) {
                try {
                    $user->setBirthday(new \DateTime($birthday));
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Invalid birthday format');
                    return $this->redirectToRoute('app_register');
                }
            }
            
            // Set role (default to 'user')
            $user->setRole('user');
            
            // Set verification code
            $user->setVerificationCode(bin2hex(random_bytes(16)));
            
            // Encode and set password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
            
            // Generate TOTP secret
            $totpSecret = $this->totpService->generateSecret();
            $user->setTotpSecret($totpSecret);
            $user->setTotpEnabled(false); // Not enabled until verified
            
            // Save user to database
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Redirect to TOTP setup page
            return $this->redirectToRoute('app_totp_setup', ['id' => $user->getId()]);
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Registration failed: ' . $e->getMessage());
            return $this->redirectToRoute('app_register');
        }
    }
}
