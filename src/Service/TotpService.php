<?php

namespace App\Service;

use OTPHP\TOTP;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use App\Entity\Users;
use ParagonIE\ConstantTime\Base32;

class TotpService
{
    private $issuer = 'GoTrip App';

    /**
     * Generate a new TOTP secret for the user
     */
    public function generateSecret(): string
    {
        // Use random_bytes instead of the protected TOTP::generateSecret()
        $secretKey = random_bytes(32);
        return trim(Base32::encodeUpper($secretKey));
    }

    /**
     * Manual TOTP code verification with debugging
     */
    public function verifyCode(Users $user, string $code): bool
    {
        if (!$user->getTotpSecret()) {
            error_log('No TOTP secret set for user: ' . $user->getEmail());
            return false;
        }

        try {
            $secret = $user->getTotpSecret();
            $currentTime = time();
            
            // Debug information
            error_log('------ TOTP Verification Debug ------');
            error_log('Email: ' . $user->getEmail());
            error_log('Code length: ' . strlen($code));
            error_log('Time: ' . date('Y-m-d H:i:s', $currentTime));
            error_log('Secret begins with: ' . substr($secret, 0, 5) . '...');

            // Try direct verification
            try {
                // Create TOTP object with 30-second period (standard)
                $totp = TOTP::create($secret);
                $totp->setLabel($user->getEmail());
                $totp->setIssuer($this->issuer);
                
                // Verify with a VERY wide window (±5 periods = ±150 seconds)
                // This compensates for large time differences between server and phone
                $result = $totp->verify($code, null, 5);
                
                error_log('Standard verification result: ' . ($result ? 'SUCCESS' : 'FAILURE'));
                
                if ($result) {
                    return true;
                }
            } catch (\Exception $e) {
                error_log('Standard verification exception: ' . $e->getMessage());
            }
            
            // If standard verification fails, try manual verification with a larger window
            // Calculate possible codes for a wider time window
            for ($offset = -10; $offset <= 10; $offset++) {
                $timestamp = $currentTime + ($offset * 30);
                try {
                    $totp = TOTP::create($secret);
                    $expectedCode = $totp->at($timestamp);
                    
                    error_log("Testing code at offset $offset: Expected $expectedCode vs Provided $code");
                    
                    if (hash_equals($expectedCode, $code)) {
                        error_log("** MANUAL MATCH FOUND at offset $offset **");
                        return true;
                    }
                } catch (\Exception $e) {
                    error_log("Error at offset $offset: " . $e->getMessage());
                }
            }
            
            error_log('Manual verification failed - no matching code found');
            return false;
        } catch (\Exception $e) {
            error_log('TOTP verification critical error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Generate QR code image as a data URI
     */
    public function getQrCodeUri(Users $user): string
    {
        if (!$user->getTotpSecret()) {
            return '';
        }

        $totp = TOTP::create($user->getTotpSecret());
        $totp->setLabel($user->getEmail());
        $totp->setIssuer($this->issuer);

        $qrCodeUri = $totp->getProvisioningUri();

        // Create QR code as SVG
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        $qrCode = $writer->writeString($qrCodeUri);

        // Convert to data URI
        return 'data:image/svg+xml;base64,' . base64_encode($qrCode);
    }
}
