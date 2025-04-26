<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use App\Service\HCaptchaService;
use App\Service\ResetPasswordService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;

class ResetPasswordController extends AbstractController
{
    public function __construct(
        private UsersRepository $usersRepository,
        private EntityManagerInterface $entityManager,
        private ResetPasswordService $resetPasswordService,
        private HCaptchaService $hCaptchaService,
        private string $hcaptchaSitekey,
        private ?LoggerInterface $logger = null
    ) {
    }

    #[Route('/reset-password-request', name: 'app_reset_password_request')]
    public function request(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            
            // Validate hCaptcha
            $hcaptchaResponse = $request->request->get('h-captcha-response');
            if (!$hcaptchaResponse || !$this->hCaptchaService->verify($hcaptchaResponse)) {
                $this->addFlash('error', 'Please complete the captcha verification.');
                return $this->render('security/reset_password_request.html.twig', [
                    'hcaptcha_sitekey' => $this->hcaptchaSitekey
                ]);
            }
            
            // Check if user exists with this email
            $user = $this->usersRepository->findOneBy(['email' => $email]);
            
            if ($user) {
                // Generate a new token using our service
                $token = $this->resetPasswordService->createToken($email);
                
                // Generate the reset URL
                $resetUrl = $this->generateUrl('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
                
                // Create a direct SMTP transport with detailed configuration
                $transport = new EsmtpTransport(
                    'smtp.hostinger.com',    // SMTP host
                    465,                     // Port
                    true                     // Use SSL
                );
                $transport->setUsername('hala.omran@jameiconseil.org');
                $transport->setPassword('Oussama1981@');
                
                // Create the mailer with our transport
                $directMailer = new Mailer($transport);
                
                // Send the email with a well-designed HTML template
                $emailMessage = (new Email())
                    ->from(new \Symfony\Component\Mime\Address('hala.omran@jameiconseil.org', 'TripMakers'))
                    ->to($email)
                    ->subject('Your password reset request')
                    ->html("
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset=\"UTF-8\">
                            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
                            <title>Password Reset Request</title>
                            <style>
                                body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f5f7; }
                                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                .header { background-color: #3554D1; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                                .header h1 { color: white; margin: 0; font-size: 24px; }
                                .content { background-color: #ffffff; padding: 30px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                                .footer { text-align: center; margin-top: 20px; color: #6c757d; font-size: 12px; }
                                .btn { display: inline-block; background-color: #3554D1; color: white; text-decoration: none; padding: 12px 25px; border-radius: 25px; margin: 15px 0; font-weight: bold; }
                                .highlight { font-weight: bold; color: #3554D1; }
                                .warning { color: #dc3545; font-size: 12px; margin-top: 20px; }
                            </style>
                        </head>
                        <body>
                            <div class=\"container\">
                                <div class=\"header\">
                                    <h1>Password Reset Request</h1>
                                </div>
                                <div class=\"content\">
                                    <p>Hello <span class=\"highlight\">{$user->getName()}</span>,</p>
                                    <p>We received a request to reset your password for your TripMakers account. If you made this request, please click the button below to reset your password:</p>
                                    <p style=\"text-align: center;\">
                                        <a href=\"{$resetUrl}\" class=\"btn\">Reset My Password</a>
                                    </p>
                                    <p>This link will expire in <span class=\"highlight\">1 hour</span>.</p>
                                    <p class=\"warning\">If you did not request a password reset, please ignore this email or contact our support team if you have concerns.</p>
                                </div>
                                <div class=\"footer\">
                                    <p>&copy; " . date('Y') . " TripMakers. All rights reserved.</p>
                                    <p>This is an automated message, please do not reply to this email.</p>
                                </div>
                            </div>
                        </body>
                        </html>
                    ");
                
                try {
                    $directMailer->send($emailMessage);
                    $this->addFlash('success', 'A password reset link has been sent to your email address.');
                } catch (TransportExceptionInterface $e) {
                    // Log the detailed error
                    if ($this->logger) {
                        $this->logger->error('Email sending failed: ' . $e->getMessage(), [
                            'exception' => $e,
                            'email' => $email
                        ]);
                    }
                    
                    // Show more detailed error in development environment
                    if ($_SERVER['APP_ENV'] === 'dev') {
                        $this->addFlash('error', 'Email sending failed: ' . $e->getMessage());
                    } else {
                        $this->addFlash('error', 'There was a problem sending the email. Please try again later.');
                    }
                } catch (\Exception $e) {
                    // Log the detailed error
                    if ($this->logger) {
                        $this->logger->error('Unexpected error while sending email: ' . $e->getMessage(), [
                            'exception' => $e,
                            'email' => $email
                        ]);
                    }
                    
                    // Show more detailed error in development environment
                    if ($_SERVER['APP_ENV'] === 'dev') {
                        $this->addFlash('error', 'Unexpected error: ' . $e->getMessage());
                    } else {
                        $this->addFlash('error', 'There was a problem sending the email. Please try again later.');
                    }
                }
            } else {
                // We don't want to reveal whether a user exists or not, so we show the same message
                $this->addFlash('success', 'A password reset link has been sent to your email address if it exists in our system.');
            }
            
            return $this->redirectToRoute('app_reset_password_request');
        }
        
        return $this->render('security/reset_password_request.html.twig', [
            'hcaptcha_sitekey' => $this->hcaptchaSitekey
        ]);
    }
    
    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function reset(string $token, Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Clean expired tokens
        $this->resetPasswordService->cleanExpiredTokens();
        
        // Get token data
        $tokenData = $this->resetPasswordService->getTokenData($token);
        
        if (!$tokenData) {
            $this->addFlash('error', 'The password reset link is invalid or has expired.');
            return $this->redirectToRoute('app_reset_password_request');
        }
        
        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $passwordConfirm = $request->request->get('password_confirm');
            
            // Validate hCaptcha
            $hcaptchaResponse = $request->request->get('h-captcha-response');
            if (!$hcaptchaResponse || !$this->hCaptchaService->verify($hcaptchaResponse)) {
                $this->addFlash('error', 'Please complete the captcha verification.');
                return $this->render('security/reset_password.html.twig', [
                    'token' => $token,
                    'hcaptcha_sitekey' => $this->hcaptchaSitekey
                ]);
            }
            
            if ($password !== $passwordConfirm) {
                $this->addFlash('error', 'The passwords do not match.');
                return $this->render('security/reset_password.html.twig', [
                    'token' => $token,
                    'hcaptcha_sitekey' => $this->hcaptchaSitekey
                ]);
            }
            
            // Validate password strength
            if (strlen($password) < 8 || 
                !preg_match('/[A-Z]/', $password) || 
                !preg_match('/[0-9]/', $password) || 
                !preg_match('/[^A-Za-z0-9]/', $password)) {
                
                $this->addFlash('error', 'Password must be at least 8 characters long and include an uppercase letter, a number, and a special character.');
                return $this->render('security/reset_password.html.twig', [
                    'token' => $token,
                    'hcaptcha_sitekey' => $this->hcaptchaSitekey
                ]);
            }
            
            // Find the user
            $user = $this->usersRepository->findOneBy(['email' => $tokenData['email']]);
            
            if (!$user) {
                $this->addFlash('error', 'User not found.');
                return $this->redirectToRoute('app_reset_password_request');
            }
            
            // Update the user's password
            $encodedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($encodedPassword);
            
            // Mark the token as used
            $this->resetPasswordService->markTokenAsUsed($token);
            
            $this->entityManager->flush();
            
            // Terminate any existing session - force logout after password reset for security
            // This will ensure the user needs to log in with their new password
            $this->get('security.token_storage')->setToken(null);
            $request->getSession()->invalidate();
            
            // Set up a success parameter in the URL to ensure message appears
            return $this->redirectToRoute('app_login', [
                'password_reset' => 'success'
            ]);
        }
        
        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
            'hcaptcha_sitekey' => $this->hcaptchaSitekey
        ]);
    }
}
