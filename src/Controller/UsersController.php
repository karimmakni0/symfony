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
    // Commenting out the original register method to use our 2FA version instead
    // #[Route('/register', name: 'app_register')]
    public function registerOld(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        // This method is disabled to use the 2FA registration flow
        return $this->redirectToRoute('app_register');
        
        /*
        $user = new Users();

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $lastname = $request->request->get('lastname');
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');
            $phone = $request->request->get('phone');
            $birthday = $request->request->get('birthday');
            $gender = $request->request->get('gender');

            $errors = [];

            if (empty($name)) $errors[] = 'First name is required';
            if (empty($lastname)) $errors[] = 'Last name is required';
            if (empty($email)) $errors[] = 'Email is required';
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email is not valid';
            if (empty($password)) $errors[] = 'Password is required';
            if ($password !== $confirmPassword) $errors[] = 'Passwords do not match';

            $existingUser = $entityManager->getRepository(Users::class)->findOneBy(['email' => $email]);
            if ($existingUser) $errors[] = 'Email already exists';

            if (empty($errors)) {
                $user->setName($name);
                $user->setLastname($lastname);
                $user->setEmail($email);
                $user->setPhone($phone ?: null);
                $user->setGender($gender ?: null);

                // Convert birthday string to DateTime object
                if ($birthday) {
                    try {
                        $birthdayDate = new \DateTime($birthday);
                        $user->setBirthday($birthdayDate);
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Invalid birthday date format.');
                    }
                }

                $user->setRole('user');
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Registration successful! You can now log in.');
                return $this->redirectToRoute('app_login');
            }

            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->render('security/register.html.twig');
        */
    }

}
