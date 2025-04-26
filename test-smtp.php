<?php
// Direct SMTP test script
require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Mailer;

// Create a direct SMTP transport with detailed configuration
$transport = new EsmtpTransport(
    'smtp.hostinger.com',    // SMTP host
    465,                     // Port
    true                     // Use SSL
);
$transport->setUsername('hala.omran@jameiconseil.org');
$transport->setPassword('Oussama1981@');

// Create the mailer with our transport
$mailer = new Mailer($transport);

// Create the email
$email = (new Email())
    ->from('hala.omran@jameiconseil.org')
    ->to('hala.omran@jameiconseil.org') // Send to yourself as a test
    ->subject('SMTP Test Email from GoTrip')
    ->html('<p>Hello!</p><p>This is a test email to verify SMTP configuration is working.</p><p>Time: ' . date('Y-m-d H:i:s') . '</p>');

try {
    // Send the email
    $mailer->send($email);
    echo "Test email sent successfully!\n";
} catch (\Exception $e) {
    echo "Failed to send email: " . $e->getMessage() . "\n";
    echo "Error type: " . get_class($e) . "\n";
    
    // Print more detailed information
    if (method_exists($e, 'getDebug')) {
        echo "Debug: " . $e->getDebug() . "\n";
    }
}
