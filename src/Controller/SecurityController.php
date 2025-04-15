<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/api", name="api_")
 */
class SecurityController extends AbstractController
{
    private $entityManager;
    private $usersRepository;
    private $passwordEncoder;
    private $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        UsersRepository $usersRepository,
        UserPasswordEncoderInterface $passwordEncoder,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->usersRepository = $usersRepository;
        $this->passwordEncoder = $passwordEncoder;
        $this->validator = $validator;
    }

   /**
 * @Route("/register", name="register", methods={"POST"})
 */
public function register(Request $request, SerializerInterface $serializer): Response
{
    $data = json_decode($request->getContent(), true);

    if (!$data) {
        return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
    }

    $user = new Users();
    $user->setName($data['name'] ?? '');
    $user->setLastname($data['lastname'] ?? '');
    $user->setEmail($data['email'] ?? '');
    $user->setRole($data['role'] ?? 'user');

    // Handle gender safely
    $validGenders = ['Male', 'Female', 'Other'];
    if (isset($data['gender']) && in_array($data['gender'], $validGenders)) {
        $user->setGender($data['gender']);
    } else {
        $user->setGender(null); // Or return error if you want to enforce it
    }

    // Handle phone
    $user->setPhone($data['phone'] ?? null);

    // Parse birthday
    if (!empty($data['birthday'])) {
        try {
            $user->setBirthday(new \DateTime($data['birthday']));
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid birthday date format'], Response::HTTP_BAD_REQUEST);
        }
    }

    // Set verification code & enabled state
    $user->setVerificationCode(bin2hex(random_bytes(16)));
    $user->setEnabled(false);

    // Password check
    if (empty($data['password']) || strlen($data['password']) < 6) {
        return new JsonResponse(['error' => 'Password must be at least 6 characters long'], Response::HTTP_BAD_REQUEST);
    }

    // Encode password
    $encodedPassword = $this->passwordEncoder->encodePassword($user, $data['password']);
    $user->setPassword($encodedPassword);

    // Check for existing email
    if ($this->usersRepository->findByEmail($user->getEmail())) {
        return new JsonResponse(['error' => 'Email already registered'], Response::HTTP_CONFLICT);
    }

    // Validate entity
    $errors = $this->validator->validate($user);
    if (count($errors) > 0) {
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[$error->getPropertyPath()] = $error->getMessage();
        }
        return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
    }

    // Save user
    $this->entityManager->persist($user);
    $this->entityManager->flush();

    // Optional: Send verification email here

    return new JsonResponse([
        'message' => 'Registration successful! Please check your email to verify your account.',
        'user_id' => $user->getId(),
    ], Response::HTTP_CREATED);
}


    /**
     * @Route("/login", name="login", methods={"POST"})
     */
    public function login(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse(['error' => 'Email and password are required'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->usersRepository->findByEmail($data['email']);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        // Check if user is enabled
        if (!$user->isEnabled()) {
            return new JsonResponse(['error' => 'Account not verified. Please check your email for verification instructions.'], Response::HTTP_UNAUTHORIZED);
        }

        // Verify password
        if (!$this->passwordEncoder->isPasswordValid($user, $data['password'])) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        // Return user data
        return new JsonResponse([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                // Add other user data as needed
            ]
        ], Response::HTTP_OK);
    }

    /**
     * @Route("/verify/{code}", name="verify_email", methods={"GET"})
     */
    public function verifyEmail(string $code): Response
    {
        $user = $this->usersRepository->findByVerificationCode($code);

        if (!$user) {
            return new JsonResponse(['error' => 'Invalid verification code'], Response::HTTP_BAD_REQUEST);
        }

        $user->setVerificationCode(null);
        $user->setEnabled(true);
        
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Account verified successfully! You can now log in.'], Response::HTTP_OK);
    }
}
