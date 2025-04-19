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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    private $entityManager;
    private $usersRepository;
    private $passwordHasher;
    private $totpService;
    private $session;

    public function __construct(
        EntityManagerInterface $entityManager,
        UsersRepository $usersRepository,
        UserPasswordHasherInterface $passwordHasher,
        TotpService $totpService,
        SessionInterface $session
    ) {
        $this->entityManager = $entityManager;
        $this->usersRepository = $usersRepository;
        $this->passwordHasher = $passwordHasher;
        $this->totpService = $totpService;
        $this->session = $session;
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

        // If there's a pending registration in session, redirect to TOTP setup
        if ($this->session->has('pending_registration_id')) {
            $userId = $this->session->get('pending_registration_id');
            return $this->redirectToRoute('app_totp_setup', ['id' => $userId]);
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
        if (!$name || !$lastname || !$email) {
            $this->addFlash('error', 'All required fields must be filled');
            return $this->redirectToRoute('app_register');
        }

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
            
            // Important: Set user as disabled until OTP verification is complete
            $user->setEnabled(false);
            
            // Save user to database as a temporary account
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Store the user ID in session for the next step
            $this->session->set('pending_registration_id', $user->getId());
            $this->session->set('registration_email', $user->getEmail());

            // Redirect to TOTP setup page
            return $this->redirectToRoute('app_totp_setup', ['id' => $user->getId()]);
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Registration failed: ' . $e->getMessage());
            return $this->redirectToRoute('app_register');
        }
    }
}
