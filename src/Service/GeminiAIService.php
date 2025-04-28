<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class GeminiAIService
{
    private const GEMINI_API_KEY = 'AIzaSyBm7SJfnl5UEqMusZ4lu6sWetpBD-sCeQU';
    private const TEXT_ENDPOINT = 'https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent';
    private const VISION_ENDPOINT = 'https://generativelanguage.googleapis.com/v1/models/gemini-pro-vision:generateContent';

    private $httpClient;
    private $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger = null
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Generate content using Gemini AI based on activity information
     *
     * @param string $activityName The name of the activity
     * @param string $destination The destination where the activity takes place
     * @param UploadedFile|null $image Optional image file to analyze
     * @return string|null Generated content or null on failure
     */
    public function generateBlogContent(string $activityName, string $destination, ?UploadedFile $image = null): ?string
    {
        try {
            $endpoint = self::TEXT_ENDPOINT;
            
            // Base request data for text-only generation
            $requestData = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => "Write a detailed travel blog post about this activity: {$activityName} in {$destination}. "
                                       . "Include what makes this activity special, what to expect, and some tips for travelers. "
                                       . "Format the response in HTML with appropriate headings, paragraphs, and occasional emphasis. "
                                       . "The post should be informative, engaging and around 500-800 words."
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 1024,
                    'topP' => 0.95
                ]
            ];
            
            // If image is provided, modify the endpoint and data for multimodal analysis
            if ($image && $image->isValid()) {
                $endpoint = self::VISION_ENDPOINT;
                
                // Convert image to base64
                $imageContent = file_get_contents($image->getPathname());
                $base64Image = base64_encode($imageContent);
                
                // Add image to the request
                $requestData['contents'][0]['parts'] = [
                    [
                        'text' => "Write a detailed travel blog post about this activity based on the image and this information: {$activityName} in {$destination}. "
                               . "Describe what you see in the image and include what makes this activity special, what to expect, and some tips for travelers. "
                               . "Format the response in HTML with appropriate headings, paragraphs, and occasional emphasis. "
                               . "The post should be informative, engaging and around 500-800 words."
                    ],
                    [
                        'inlineData' => [
                            'mimeType' => $image->getMimeType(),
                            'data' => $base64Image
                        ]
                    ]
                ];
            }
            
            // Make the API request
            $response = $this->httpClient->request('POST', $endpoint . '?key=' . self::GEMINI_API_KEY, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => $requestData
            ]);
            
            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                $data = $response->toArray();
                
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    return $data['candidates'][0]['content']['parts'][0]['text'];
                }
            } else {
                $this->logger?->warning('Gemini returned non-200 status', [
                    'endpoint' => $endpoint,
                    'status' => $statusCode,
                    'response' => $response->getContent(false)
                ]);
            }
        } catch (\Exception $e) {
            $this->logger?->warning('Exception when calling Gemini API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return null;
    }
}
