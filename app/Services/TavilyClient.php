<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use RuntimeException;

class TavilyClient
{
    private const API_URL = 'https://api.tavily.com/search';
    private string $apiKey;

    public function __construct()
    {
        $apiKey = env('TAVILY_API_KEY');
        if (empty($apiKey)) {
            throw new RuntimeException('TAVILY_API_KEY not found in environment variables');
        }
        $this->apiKey = $apiKey;
    }

    /**
     * Perform a search query
     *
     * @param string $query Search query
     * @param array $options Search options
     * @return array Search results
     * @throws RuntimeException If the API call fails
     */
    public function search(string $query, array $options = []): array
    {
        $response = $this->makeRequest(trim($query), $options);

        if ($response->successful()) {
            try {
                $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
                if (is_array($data)) {
                    return $data;
                }
            } catch (\JsonException $e) {
                // Fall through to error handling
            }
            throw new RuntimeException('Tavily API request failed: Unknown error');
        }

        try {
            $error = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
            $message = $error['error']['message'] ?? 'Unknown error';
        } catch (\JsonException $e) {
            $message = 'Unknown error';
        }
        
        throw new RuntimeException(
            "Tavily API request failed: {$message}",
            $response->status()
        );
    }

    /**
     * Make the HTTP request to Tavily's API
     *
     * @param string $query
     * @param array $options
     * @return Response
     */
    private function makeRequest(string $query, array $options): Response
    {
        if (empty($query)) {
            $query = 'empty query';
        }

        $payload = array_merge([
            'query' => $query,
            'search_depth' => $options['search_depth'] ?? 'advanced',
            'include_domains' => $options['include_domains'] ?? [],
            'exclude_domains' => $options['exclude_domains'] ?? [],
            'include_answer' => $options['include_answer'] ?? true,
            'max_results' => $options['max_results'] ?? 10,
            'api_key' => $this->apiKey,
        ], $options);

        return Http::post(self::API_URL, $payload);
    }

    /**
     * Perform a job-specific search
     *
     * @param string $query Search query
     * @param array $options Search options
     * @return array Search results filtered for job listings
     */
    public function searchJobs(string $query, array $options = []): array
    {
        // Add job-specific domains to include
        $jobDomains = [
            'linkedin.com',
            'indeed.com',
            'glassdoor.com',
            'monster.com',
            'careers.google.com',
            'jobs.lever.co',
            'greenhouse.io',
            'wellfound.com',
        ];

        $options['include_domains'] = array_merge(
            $options['include_domains'] ?? [],
            $jobDomains
        );

        // Add job-specific keywords to the query
        $jobQuery = trim($query . ' job career position opening hiring');

        return $this->search($jobQuery, array_merge([
            'search_depth' => 'advanced',
            'max_results' => 20,
        ], $options));
    }
} 