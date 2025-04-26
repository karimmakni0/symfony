<?php

namespace App\Service;

use App\Entity\Users;
use App\Entity\UserLoginHistory;
use App\Repository\UserLoginHistoryRepository;
use App\Service\ResetPasswordService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LoginNotificationService
{
    private $entityManager;
    private $loginHistoryRepository;
    private $deviceDetectionService;
    private $urlGenerator;
    private $resetPasswordService;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserLoginHistoryRepository $loginHistoryRepository,
        DeviceDetectionService $deviceDetectionService,
        UrlGeneratorInterface $urlGenerator,
        ResetPasswordService $resetPasswordService
    ) {
        $this->entityManager = $entityManager;
        $this->loginHistoryRepository = $loginHistoryRepository;
        $this->deviceDetectionService = $deviceDetectionService;
        $this->urlGenerator = $urlGenerator;
        $this->resetPasswordService = $resetPasswordService;
    }

    public function recordLogin(Users $user, Request $request): bool
    {
        try {
            $userAgent = $request->headers->get('User-Agent');
            $ipAddress = $request->getClientIp();
            $deviceInfo = $this->deviceDetectionService->getDeviceInfo($userAgent);
            
            // Always return true for now to ensure notification emails are sent
            // This will make the system always send notification emails for testing purposes
            $isNewDevice = true;
            
            // For debugging
            error_log("Login recorded for user: {$user->getEmail()}, IP: {$ipAddress}, FORCING notification email");
            
            return $isNewDevice;
            
            /* To be implemented in future
            // Check if this is a new device/location for this user
            $existingLogin = $this->loginHistoryRepository->findOneBy([
                'user' => $user,
                'ipAddress' => $ipAddress,
            ]);
            
            // Record the login
            $loginHistory = new UserLoginHistory();
            $loginHistory->setUser($user);
            $loginHistory->setIpAddress($ipAddress);
            $loginHistory->setUserAgent($userAgent);
            $loginHistory->setDeviceType($deviceInfo['device']);
            $loginHistory->setPlatform($deviceInfo['platform']);
            $loginHistory->setBrowser($deviceInfo['browser']);
            $loginHistory->setLoginTime(new \DateTime());
            
            $this->entityManager->persist($loginHistory);
            $this->entityManager->flush();
            */
        } catch (\Exception $e) {
            // Log error but don't crash the login process
            error_log("Error recording login: " . $e->getMessage());
            return true; // Always send notification if there's an error, just to be safe
        }
    }
    
    public function sendLoginNotificationEmail(Users $user, Request $request): void
    {
        try {
            $userAgent = $request->headers->get('User-Agent');
            $ipAddress = $request->getClientIp();
            $deviceInfo = $this->deviceDetectionService->getDeviceInfo($userAgent);
            $readableDevice = $this->deviceDetectionService->getReadableDeviceInfo($userAgent);
            $loginTime = new \DateTime();
            
            // Create a reset token using the existing ResetPasswordService
            // This properly generates and stores the token without database issues
            try {
                $token = $this->resetPasswordService->createToken($user->getEmail());
                error_log("Generated reset password token for security notification: " . $token);
                
                // Generate a direct reset URL that bypasses the email request step
                $resetUrl = $this->urlGenerator->generate('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
            } catch (\Exception $e) {
                error_log("Failed to generate reset token: " . $e->getMessage());
                // Fallback to the request page if token generation fails
                $resetUrl = $this->urlGenerator->generate('app_reset_password_request', [], UrlGeneratorInterface::ABSOLUTE_URL);
            }
            
            // Create direct SMTP transport
            $transport = new EsmtpTransport(
                'smtp.hostinger.com',
                465,
                true
            );
            $transport->setUsername('hala.omran@jameiconseil.org');
            $transport->setPassword('Oussama1981@');
            
            // Create mailer
            $mailer = new Mailer($transport);
            
            // Log before sending email
            error_log("Sending login notification email to: {$user->getEmail()}, Device: {$readableDevice}");
            
            // Build email
            $email = (new Email())
                ->from(new Address('hala.omran@jameiconseil.org', 'TripMakers Security'))
                ->to($user->getEmail())
                ->subject('New login to your TripMakers account')
                ->html($this->getLoginNotificationEmailTemplate(
                    $user->getName(),
                    $loginTime,
                    $ipAddress,
                    $readableDevice,
                    $resetUrl
                ));
            
            // Send email
            $mailer->send($email);
            
            // Log success
            error_log("Login notification email successfully sent to: {$user->getEmail()}");
        } catch (\Exception $e) {
            // Log detailed error
            error_log("Failed to send login notification email: " . $e->getMessage());
            error_log("Exception trace: " . $e->getTraceAsString());
        }
    }
    
    private function getLoginNotificationEmailTemplate(
        string $userName,
        \DateTime $loginTime,
        string $ipAddress,
        string $deviceInfo,
        string $resetUrl
    ): string {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Login Alert</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f5f7; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #3554D1; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .header h1 { color: white; margin: 0; font-size: 24px; }
        .content { background-color: #ffffff; padding: 30px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .footer { text-align: center; margin-top: 20px; color: #6c757d; font-size: 12px; }
        .btn { display: inline-block; background-color: #3554D1; color: white; text-decoration: none; padding: 12px 25px; border-radius: 25px; margin: 15px 0; font-weight: bold; }
        .btn-warning { background-color: #dc3545; }
        .highlight { font-weight: bold; color: #3554D1; }
        .warning { color: #dc3545; font-weight: bold; }
        .login-details { background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #3554D1; }
        .login-details p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Login Alert</h1>
        </div>
        <div class="content">
            <p>Hello <span class="highlight">{$userName}</span>,</p>
            <p>We detected a new login to your TripMakers account. If this was you, you can safely ignore this email.</p>
            
            <div class="login-details">
                <p><strong>Time:</strong> {$loginTime->format('F j, Y, g:i a')}</p>
                <p><strong>IP Address:</strong> {$ipAddress}</p>
                <p><strong>Device:</strong> {$deviceInfo}</p>
            </div>
            
            <p class="warning">If you didn't login recently, your account may have been compromised.</p>
            <p>To secure your account, you should immediately:</p>
            <p style="text-align: center;">
                <a href="{$resetUrl}" class="btn btn-warning">Reset Your Password</a>
            </p>
        </div>
        <div class="footer">
            <p>&copy; {$loginTime->format('Y')} TripMakers. All rights reserved.</p>
            <p>This is an automated security message from TripMakers. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
