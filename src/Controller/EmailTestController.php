<?php

namespace App\Controller;

use App\Repository\ActivitiesRepository;
use App\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmailTestController extends AbstractController
{
    private $emailService;
    private $activitiesRepository;

    public function __construct(
        EmailService $emailService,
        ActivitiesRepository $activitiesRepository
    ) {
        $this->emailService = $emailService;
        $this->activitiesRepository = $activitiesRepository;
    }

    #[Route('/admin/test-activity-email', name: 'app_test_activity_email')]
    public function testActivityEmail(): Response
    {
        // Get a sample activity to use for the test
        $activity = $this->activitiesRepository->findOneBy([]);
        
        if (!$activity) {
            return new Response('No activities found to test with.');
        }

        try {
            // Get the MAILER_DSN from environment
            $mailerDsn = $_ENV['MAILER_DSN'] ?? 'Not configured';
            $maskedDsn = preg_replace('/:[^:@]+@/', ':***@', $mailerDsn);
            
            // Log all users that would receive emails
            $usersRepo = $this->getDoctrine()->getRepository('App\Entity\Users');
            $users = $usersRepo->findAll();
            $usersList = [];
            
            foreach ($users as $user) {
                $usersList[] = [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getFirstname() . ' ' . $user->getLastname()
                ];
            }
            
            // Send the test email and capture results
            $result = $this->emailService->sendNewActivityNotification($activity);
            
            // Display detailed results
            $resultHtml = '<h2>Email Sending Results</h2>';
            $resultHtml .= '<p><strong>Emails sent:</strong> ' . $result['sent'] . '</p>';
            $resultHtml .= '<p><strong>Emails failed:</strong> ' . $result['failed'] . '</p>';
            $resultHtml .= '<p><strong>Recipients skipped:</strong> ' . $result['skipped'] . '</p>';
            
            if (!empty($result['errors'])) {
                $resultHtml .= '<h3>Errors:</h3><ul>';
                foreach ($result['errors'] as $error) {
                    $resultHtml .= '<li>' . htmlspecialchars($error) . '</li>';
                }
                $resultHtml .= '</ul>';
            }
            
            // User list
            $userHtml = '<h2>Users in System</h2>';
            $userHtml .= '<table border="1" cellpadding="5"><tr><th>ID</th><th>Email</th><th>Name</th></tr>';
            foreach ($usersList as $u) {
                $userHtml .= '<tr>';
                $userHtml .= '<td>' . $u['id'] . '</td>';
                $userHtml .= '<td>' . htmlspecialchars($u['email']) . '</td>';
                $userHtml .= '<td>' . htmlspecialchars($u['name']) . '</td>';
                $userHtml .= '</tr>';
            }
            $userHtml .= '</table>';
            
            return new Response(
                '<html><body>
                    <h1>Email Test Results</h1>
                    <p>Test email notification for activity "' . $activity->getActivityName() . '" was processed.</p>
                    <p><strong>Mailer Configuration:</strong> ' . $maskedDsn . '</p>
                    ' . $resultHtml . '
                    ' . $userHtml . '
                    <h3>Log Files</h3>
                    <p>Check the following log files for more details:</p>
                    <ul>
                        <li>/var/log/email_service.log</li>
                        <li>/var/log/email_notification_attempt.log</li>
                        <li>/var/log/email_notification_success.log</li>
                        <li>/var/log/email_notification_error.log</li>
                    </ul>
                </body></html>'
            );
        } catch (\Exception $e) {
            return new Response(
                '<html><body>
                    <h1>Email Test Failed</h1>
                    <p>Error: ' . $e->getMessage() . '</p>
                    <pre>' . $e->getTraceAsString() . '</pre>
                </body></html>'
            );
        }
    }
}
