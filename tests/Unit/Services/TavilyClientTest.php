<?php

namespace Tests\Unit\Services;

use App\Services\TavilyClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class TavilyClientTest extends TestCase
{
    private TavilyClient $client;
    private string $originalApiKey;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Store original API key
        $this->originalApiKey = env('TAVILY_API_KEY', '');
        
        // Mock the environment variable
        $this->app['config']->set('services.tavily.key', 'test-key');
        putenv('TAVILY_API_KEY=test-key');
        
        $this->client = new TavilyClient();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Restore original API key
        if ($this->originalApiKey) {
            putenv('TAVILY_API_KEY=' . $this->originalApiKey);
        } else {
            putenv('TAVILY_API_KEY');
        }
    }

    /**
     * Test successful search request
     */
    public function test_search_successful_request(): void
    {
        // Mock successful API response
        Http::fake([
            'api.tavily.com/search' => Http::response([
                'results' => [
                    [
                        'title' => 'Test Job',
                        'url' => 'https://example.com/job',
                        'content' => 'Test content'
                    ]
                ]
            ], 200)
        ]);

        $result = $this->client->search('test query');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
        $this->assertCount(1, $result['results']);
    }
} 