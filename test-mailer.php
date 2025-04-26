<?php
// Standalone script to test Symfony mailer with Gmail

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Mailer\Bridge\Google\Transport\GmailSmtpTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Mailer;

// Create a Gmail transport with the app password
$transport = new GmailSmtpTransport('justforlocalhost@gmail.com', 'kfts wqvw yhgs volz');
$mailer = new Mailer($transport);

// Create the email
$email = (new Email())
    ->from('justforlocalhost@gmail.com')
    ->to('justforlocalhost@gmail.com') // Send to yourself as a test
    ->subject('Test Email from GoTrip')
    ->html('<p>Hello!</p><p>This is a test email to verify the SMTP configuration is working correctly for the password reset functionality.</p><p>Best regards,<br>GoTrip Team</p>');

try {
    // Send the email
    $mailer->send($email);
    echo "Test email sent successfully!\n";
} catch (\Exception $e) {
    echo "Failed to send email: " . $e->getMessage() . "\n";
}
