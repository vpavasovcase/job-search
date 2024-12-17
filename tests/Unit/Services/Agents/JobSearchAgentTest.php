<?php

namespace Tests\Unit\Services\Agents;

use App\Models\JobCriteria;
use App\Services\Agents\JobSearchAgent;
use App\Services\AnthropicClient;
use App\Services\TavilyClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class JobSearchAgentTest extends TestCase
{
    use RefreshDatabase;

    private JobSearchAgent $agent;
    private TavilyClient $tavilyMock;
    private AnthropicClient $anthropicMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tavilyMock = $this->createMock(TavilyClient::class);
        $this->anthropicMock = $this->createMock(AnthropicClient::class);
        
        $this->agent = new JobSearchAgent(
            $this->tavilyMock,
            $this->anthropicMock
        );
    }

    /**
     * Test job search with matching criteria
     */
    public function test_search_jobs_with_matching_criteria(): void
    {
        // Create test job criteria
        $criteria = JobCriteria::factory()->create([
            'title' => 'Software Engineer',
            'keywords' => ['php', 'laravel', 'vue'],
            'location' => 'San Francisco',
            'min_salary' => 120000,
            'job_type' => 'full-time'
        ]);

        // Mock Tavily search results
        $this->tavilyMock
            ->expects($this->once())
            ->method('searchJobs')
            ->willReturn([
                'results' => [
                    [
                        'title' => 'Senior Software Engineer',
                        'url' => 'https://linkedin.com/jobs/123',
                        'content' => 'We are looking for a PHP developer...'
                    ]
                ]
            ]);

        // Mock Claude's analysis
        $this->anthropicMock
            ->expects($this->once())
            ->method('generateText')
            ->willReturn(json_encode([
                'matches_criteria' => true,
                'reason' => 'Skills and location match',
                'extracted_info' => [
                    'title' => 'Senior Software Engineer',
                    'company' => 'Example Corp',
                    'location' => 'San Francisco, CA',
                    'salary_min' => 130000,
                    'salary_max' => 180000,
                    'job_type' => 'full-time',
                    'required_skills' => ['php', 'laravel'],
                    'preferred_skills' => ['vue', 'react'],
                    'description' => 'We are looking for a PHP developer...'
                ],
                'confidence_score' => 0.95
            ]));

        $jobs = $this->agent->searchJobs($criteria);

        $this->assertCount(1, $jobs);
        $job = $jobs->first();
        
        $this->assertEquals('Senior Software Engineer', $job->title);
        $this->assertEquals('Example Corp', $job->company);
        $this->assertEquals('San Francisco, CA', $job->location);
        $this->assertEquals(130000, $job->salary_min);
        $this->assertEquals(180000, $job->salary_max);
        $this->assertEquals('full-time', $job->job_type);
    }
} 