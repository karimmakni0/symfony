<?php

namespace App\Service;

use App\Entity\Activities;
use App\Entity\Users;
use App\Repository\UsersRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailService
{
    private $mailer;
    private $usersRepository;
    private $urlGenerator;

    public function __construct(
        MailerInterface $mailer,
        UsersRepository $usersRepository,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->mailer = $mailer;
        $this->usersRepository = $usersRepository;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Send notification email to all users about a new activity
     * @return array Returns a summary of the email sending operation
     */
    public function sendNewActivityNotification(Activities $activity): array
    {
        // Initialize result tracking
        $result = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => []
        ];
        
        // Log the start of email sending
        $this->logMessage("Starting email notifications for activity ID: {$activity->getId()}");
        // Get all users
        $users = $this->usersRepository->findAll();
        $this->logMessage("Found " . count($users) . " users to notify");
        
        // Generate activity URL
        try {
            $activityUrl = $this->urlGenerator->generate(
                'app_activities_details', 
                ['id' => $activity->getId()], 
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $this->logMessage("Generated activity URL: {$activityUrl}");
        } catch (\Exception $e) {
            $this->logMessage("Error generating URL: {$e->getMessage()}", 'error');
            $activityUrl = '/client/activities/' . $activity->getId();
            $result['errors'][] = "URL generation error: {$e->getMessage()}";
        }
        
        // Get activity details
        $activityName = $activity->getActivityName();
        $activityDestination = $activity->getActivityDestination();
        $activityGenre = $activity->getActivityGenre();
        $activityPrice = $activity->getActivityPrice();
        
        // Prepare email subject and content
        $subject = "New Activity Alert: $activityName";
        
        foreach ($users as $user) {
            // Skip sending to the publisher who created the activity
            if ($activity->getUser() && $user->getId() === $activity->getUser()->getId()) {
                $this->logMessage("Skipping user ID {$user->getId()} (creator of the activity)");
                $result['skipped']++;
                continue;
            }
            
            // Make sure user has an email
            if (empty($user->getEmail())) {
                $this->logMessage("Skipping user ID {$user->getId()} (no email address)");
                $result['skipped']++;
                continue;
            }
            
            // Create email with personalized greeting
            $userName = $user->getName() ? $user->getName() : 'Valued Customer';
            
            $htmlContent = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #3554D1; color: white; padding: 20px; text-align: center; }
                        .content { padding: 20px; background-color: #f9f9f9; }
                        .activity-details { background-color: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
                        .btn { display: inline-block; background-color: #3554D1; color: white; padding: 10px 20px; 
                               text-decoration: none; border-radius: 5px; margin-top: 15px; }
                        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>New Activity Alert!</h1>
                        </div>
                        <div class='content'>
                            <p>Hello $userName,</p>
                            <p>We're excited to announce a new activity that has just been added to our platform:</p>
                            
                            <div class='activity-details'>
                                <h2>$activityName</h2>
                                <p><strong>Destination:</strong> $activityDestination</p>
                                <p><strong>Category:</strong> $activityGenre</p>
                                <p><strong>Price:</strong> $activityPrice TND</p>
                            </div>
                            
                            <p>Don't miss out on this amazing opportunity!</p>
                            <a href='$activityUrl' class='btn'>View Activity Details</a>
                            
                            <p>We hope you find this activity interesting and look forward to helping you plan your next adventure.</p>
                            <p>Best regards,<br>The Go Trip Team</p>
                        </div>
                        <div class='footer'>
                            <p>© " . date('Y') . " Go Trip. All rights reserved.</p>
                            <p>If you prefer not to receive these notifications, please update your preferences in your account settings.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            $email = (new Email())
                ->from(new \Symfony\Component\Mime\Address('hala.omran@jameiconseil.org', 'Tripmakers'))
                ->to($user->getEmail())
                ->subject($subject)
                ->html($htmlContent);
            
            // Send the email
            try {
                $this->logMessage("Sending email to: {$user->getEmail()}");
                $this->mailer->send($email);
                $this->logMessage("Email sent successfully to: {$user->getEmail()}");
                $result['sent']++;
            } catch (\Exception $e) {
                $this->logMessage("Failed to send email to {$user->getEmail()}: {$e->getMessage()}", 'error');
                $result['failed']++;
                $result['errors'][] = "Send error for {$user->getEmail()}: {$e->getMessage()}";
            }
        }
        
        // Log summary
        $this->logMessage("Email notification summary: {$result['sent']} sent, {$result['failed']} failed, {$result['skipped']} skipped");
        
        return $result;
    }
    
    /**
     * Log a message to the email log file
     */
    private function logMessage(string $message, string $level = 'info'): void
    {
        $logDir = dirname(__DIR__, 2) . '/var/log';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $logFile = $logDir . '/email_service.log';
        $logEntry = date('Y-m-d H:i:s') . " [{$level}] " . $message . "\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
