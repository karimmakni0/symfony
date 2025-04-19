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
     * Verify TOTP code against user's secret
     */
    public function verifyCode(Users $user, string $code): bool
    {
        if (!$user->getTotpSecret()) {
            return false;
        }

        $totp = TOTP::create($user->getTotpSecret());
        $totp->setLabel($user->getEmail());
        $totp->setIssuer($this->issuer);

        return $totp->verify($code);
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
