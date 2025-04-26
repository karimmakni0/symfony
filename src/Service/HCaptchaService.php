<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class HCaptchaService
{
    private string $secret;
    private bool $enabled;
    
    public function __construct(string $hcaptchaSecret)
    {
        $this->secret = $hcaptchaSecret;
        $this->enabled = !empty($hcaptchaSecret);
    }
    
    /**
     * Verify hCaptcha response
     *
     * @param string|null $hcaptchaResponse The h-captcha-response token from the form
     * @param string|null $remoteIp The user's IP address
     * @return bool True if verification passed or if hCaptcha is disabled
     */
    public function verify(?string $hcaptchaResponse, ?string $remoteIp = null): bool
    {
        // If hCaptcha is not configured, always pass verification
        if (!$this->enabled) {
            return true;
        }
        
        // If no response token provided, verification fails
        if (empty($hcaptchaResponse)) {
            return false;
        }
        
        try {
            $client = HttpClient::create();
            $response = $client->request('POST', 'https://hcaptcha.com/siteverify', [
                'body' => [
                    'secret' => $this->secret,
                    'response' => $hcaptchaResponse,
                    'remoteip' => $remoteIp
                ]
            ]);
            
            $content = $response->toArray();
            
            return $content['success'] === true;
            
        } catch (TransportExceptionInterface $e) {
            // In case of HTTP request failure, log the error and fail closed
            // In a production environment, you might want to log this error
            return false;
        }
    }
    
    /**
     * Check if hCaptcha validation is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
