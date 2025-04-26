<?php

namespace App\Service;

class DeviceDetectionService
{
    public function getDeviceInfo(string $userAgent): array
    {
        $device = 'Unknown Device';
        $platform = 'Unknown Platform';
        $browser = 'Unknown Browser';
        
        // Detect Platform/OS
        if (preg_match('/android/i', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/iphone/i', $userAgent)) {
            $platform = 'iPhone';
        } elseif (preg_match('/ipad/i', $userAgent)) {
            $platform = 'iPad';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/windows|win32/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $platform = 'Linux';
        }
        
        // Detect Browser
        if (preg_match('/MSIE|Trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
            $browser = 'Opera';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browser = 'Edge';
        }
        
        // Detect Device Type
        if (preg_match('/mobile/i', $userAgent)) {
            $device = 'Mobile';
        } elseif (preg_match('/tablet/i', $userAgent)) {
            $device = 'Tablet';
        } else {
            $device = 'Desktop';
        }
        
        return [
            'device' => $device,
            'platform' => $platform,
            'browser' => $browser,
            'user_agent' => $userAgent
        ];
    }
    
    public function getReadableDeviceInfo(string $userAgent): string
    {
        $info = $this->getDeviceInfo($userAgent);
        return "{$info['device']} - {$info['platform']} - {$info['browser']}";
    }
}
