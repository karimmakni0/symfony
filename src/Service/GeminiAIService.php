<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class GeminiAIService
{
    // Using environment variable for API key
    private function getApiKey(): string
    {
        // First try to use the key from .env file, otherwise fall back to the alternative key
        return $_ENV['GEMINI_API_KEY'] ?? $_ENV['GEMINI_ALT_API_KEY'];
    }
    private const TEXT_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
    private const VISION_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    private $httpClient;
    private $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        ?LoggerInterface $logger = null
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
     * @param bool $stripHtml Whether to strip all HTML tags from the generated content
     * @return string|null Generated content or null on failure
     */
    public function generateBlogContent(string $activityName, string $destination, ?UploadedFile $image = null, bool $stripHtml = false): ?string
    {
        try {
            // Use the vision endpoint since image is required
            $endpoint = self::VISION_ENDPOINT;
            
            if (!$image || !$image->isValid()) {
                throw new \InvalidArgumentException('A valid image is required for generating content');
            }
            
            // Convert image to base64
            $imageContent = file_get_contents($image->getPathname());
            $base64Image = base64_encode($imageContent);
            
            // Prepare the prompt text
            $promptText = "Write a simple travel blog post about this activity: {$activityName} in {$destination}. "
                         . "Include what makes this activity special and what to expect. "
                         . "The post should be simple, engaging and around 3 sentences.";
            
            // Create request data with the image
            $requestData = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $promptText
                            ],
                            [
                                'inline_data' => [
                                    'mime_type' => $image->getMimeType(),
                                    'data' => $base64Image
                                ]
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
            
            // Log the request data for debugging
            $this->logger?->info('Sending request to Gemini API', [
                'endpoint' => $endpoint,
                'requestData' => json_encode($requestData),
            ]);
            
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
                $this->logger?->info('Gemini API response', [
                    'response' => json_encode($data)
                ]);
                
                // Handle response based on the v1beta format
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    return $this->cleanupGeneratedContent($data['candidates'][0]['content']['parts'][0]['text'], $stripHtml);
                }
                
                // Alternative response format
                if (isset($data['candidates'][0]['text'])) {
                    return $this->cleanupGeneratedContent($data['candidates'][0]['text'], $stripHtml);
                }
            } else {
                $this->logger?->error('Gemini returned non-200 status', [
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
    
    /**
     * Handle chat questions about activities using Gemini AI
     *
     * @param string $context The activity context and user question
     * @param UploadedFile|null $image Optional image related to the activity
     * @return string|null Generated response or null on failure
     */
    public function generateChatResponse(string $context, ?UploadedFile $image = null): ?string
    {
        try {
            // Use the vision endpoint if image is available, otherwise use text
            $endpoint = self::VISION_ENDPOINT;
            $hasImage = $image && $image->isValid();
            
            // Prepare the request data
            $requestParts = [
                [
                    'text' => $context
                ]
            ];
            
            // Add image if available
            if ($hasImage) {
                // Convert image to base64
                $imageContent = file_get_contents($image->getPathname());
                $base64Image = base64_encode($imageContent);
                
                $requestParts[] = [
                    'inline_data' => [
                        'mime_type' => $image->getMimeType(),
                        'data' => $base64Image
                    ]
                ];
            }
            
            $requestData = [
                'contents' => [
                    [
                        'parts' => $requestParts
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.2, // Lower temperature for more factual responses
                    'maxOutputTokens' => 1024,
                    'topP' => 0.95
                ]
            ];
            
            // Log the request data for debugging
            $this->logger?->info('Sending chat request to Gemini API', [
                'endpoint' => $endpoint,
                'hasImage' => $hasImage,
            ]);
            
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
                
                // Handle response based on the v1beta format
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    return $data['candidates'][0]['content']['parts'][0]['text'];
                }
                
                // Alternative response format
                if (isset($data['candidates'][0]['text'])) {
                    return $data['candidates'][0]['text'];
                }
            } else {
                $this->logger?->error('Gemini returned non-200 status for chat', [
                    'endpoint' => $endpoint,
                    'status' => $statusCode,
                    'response' => $response->getContent(false)
                ]);
            }
        } catch (\Exception $e) {
            $this->logger?->warning('Exception when calling Gemini API for chat', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return null;
    }
    
    /**
     * Clean up the generated content to remove any full HTML document structure
     * and format the content for better readability in the text editor
     * 
     * @param string $content The raw generated content
     * @param bool $stripAllHtml Whether to strip all HTML tags or just format them properly
     * @return string The cleaned content
     */
    private function cleanupGeneratedContent(string $content, bool $stripAllHtml = false): string
    {
        // Remove DOCTYPE, html, head, body tags and any CSS styles
        $patterns = [
            '/<\!DOCTYPE[^>]*>/i',
            '/<html[^>]*>|<\/html>/i',
            '/<head>.*?<\/head>/is',
            '/<body[^>]*>|<\/body>/i',
            '/<style[^>]*>.*?<\/style>/is',
            '/<script[^>]*>.*?<\/script>/is',
            '/<\/?(meta|title|link)[^>]*>/i'
        ];
        
        $cleaned = preg_replace($patterns, '', $content);
        
        // Also remove any img tags that might have been generated
        $cleaned = preg_replace('/<img[^>]*>/i', '', $cleaned);
        
        if ($stripAllHtml) {
            // Strip all HTML tags if requested
            $cleaned = strip_tags($cleaned);
        } else {
            // Add proper newlines before and after block elements for better formatting
            $cleaned = preg_replace('/<\/?(h[1-6]|p|ul|ol|li|div)\b[^>]*>/i', "\n$0\n", $cleaned);
            
            // Format the remaining HTML for readability in a textarea
            $cleaned = preg_replace('/></i', "> <", $cleaned);
            
            // Clean up excessive spacing that might have been introduced
            $cleaned = preg_replace('/\s*\n\s*/s', "\n", $cleaned);
            $cleaned = preg_replace('/\n{3,}/s', "\n\n", $cleaned);
        }
        
        // Trim whitespace at beginning and end
        $cleaned = trim($cleaned);
        
        // Log the cleanup for debugging
        $this->logger?->info('Content cleanup', [
            'original_length' => strlen($content),
            'cleaned_length' => strlen($cleaned),
            'chars_removed' => strlen($content) - strlen($cleaned),
            'strip_all_html' => $stripAllHtml ? 'yes' : 'no'
        ]);
        
        return $cleaned;
    }
}
