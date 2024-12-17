<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use RuntimeException;

class AnthropicClient
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private string $apiKey;
    private string $model;
    private const API_VERSION = '2024-02-15';

    public function __construct()
    {
        $this->apiKey = env('ANTHROPIC_API_KEY');
        if (!$this->apiKey) {
            throw new RuntimeException('ANTHROPIC_API_KEY not found in environment variables');
        }
        $this->model = env('ANTHROPIC_MODEL', 'claude-3-opus-20240229');
    }

    /**
     * Generate text using Anthropic's API
     *
     * @param string $prompt The input prompt for text generation
     * @param array $options Additional options for the API call
     * @return string The generated text response
     * @throws RuntimeException If the API call fails
     */
    public function generateText(string $prompt, array $options = []): string
    {
        $response = $this->makeRequest($prompt, $options);

        if ($response->successful()) {
            $data = $response->json();
            // The content is now in the 'content' array with each element having a 'text' field
            return $data['content'][0]['text'] ?? '';
        }

        $errorMessage = $response->json()['error']['message'] ?? 'Unknown error';
        $errorType = $response->json()['error']['type'] ?? 'unknown_error';
        
        throw new RuntimeException(
            "Anthropic API request failed: {$errorType} - {$errorMessage}",
            $response->status()
        );
    }

    /**
     * Make the HTTP request to Anthropic's API
     *
     * @param string $prompt
     * @param array $options
     * @return Response
     */
    private function makeRequest(string $prompt, array $options): Response
    {
        $payload = array_merge([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $options['max_tokens'] ?? 1024,
            'temperature' => $options['temperature'] ?? 0.7,
            'system' => $options['system'] ?? 'You are a professional cover letter writer with expertise in crafting compelling job applications.',
        ], $options);

        return Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-beta' => 'messages-2024-02-15',
            'content-type' => 'application/json',
        ])->post(self::API_URL, $payload);
    }

    /**
     * Set a custom model
     *
     * @param string $model
     * @return self
     */
    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Get the current model
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }
} 