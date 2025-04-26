<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ResetPasswordService
{
    private string $tokenDir;
    
    public function __construct(string $projectDir)
    {
        $this->tokenDir = $projectDir . '/var/reset_tokens';
        
        // Ensure directory exists
        $fs = new Filesystem();
        if (!$fs->exists($this->tokenDir)) {
            $fs->mkdir($this->tokenDir);
        }
    }
    
    public function createToken(string $email): string
    {
        // Generate unique token
        $token = bin2hex(random_bytes(32));
        
        // Clean existing tokens for this user
        $this->removeTokensByEmail($email);
        
        // Create token file
        $tokenData = [
            'email' => $email,
            'expires_at' => (new \DateTime('+1 hour'))->format('Y-m-d H:i:s'),
            'is_used' => false
        ];
        
        file_put_contents($this->tokenDir . '/' . $token . '.json', json_encode($tokenData));
        
        return $token;
    }
    
    public function getTokenData(string $token): ?array
    {
        $tokenFile = $this->tokenDir . '/' . $token . '.json';
        
        if (!file_exists($tokenFile)) {
            return null;
        }
        
        $tokenData = json_decode(file_get_contents($tokenFile), true);
        
        // Check if token is expired
        $expiresAt = new \DateTime($tokenData['expires_at']);
        if ($expiresAt < new \DateTime() || $tokenData['is_used']) {
            return null;
        }
        
        return $tokenData;
    }
    
    public function markTokenAsUsed(string $token): void
    {
        $tokenFile = $this->tokenDir . '/' . $token . '.json';
        
        if (!file_exists($tokenFile)) {
            return;
        }
        
        $tokenData = json_decode(file_get_contents($tokenFile), true);
        $tokenData['is_used'] = true;
        
        file_put_contents($tokenFile, json_encode($tokenData));
    }
    
    public function removeTokensByEmail(string $email): void
    {
        $finder = new Finder();
        $finder->files()->in($this->tokenDir);
        
        foreach ($finder as $file) {
            $tokenData = json_decode($file->getContents(), true);
            if ($tokenData['email'] === $email) {
                unlink($file->getRealPath());
            }
        }
    }
    
    public function cleanExpiredTokens(): void
    {
        $finder = new Finder();
        $finder->files()->in($this->tokenDir);
        
        $now = new \DateTime();
        
        foreach ($finder as $file) {
            $tokenData = json_decode($file->getContents(), true);
            $expiresAt = new \DateTime($tokenData['expires_at']);
            
            if ($expiresAt < $now) {
                unlink($file->getRealPath());
            }
        }
    }
}
