<?php

namespace App\Services\Agents;

use App\Models\Job;
use App\Models\JobCriteria;
use App\Services\TavilyClient;
use App\Services\AnthropicClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class JobSearchAgent
{
    public function __construct(
        private TavilyClient $tavily,
        private AnthropicClient $anthropic
    ) {}

    /**
     * Search for jobs based on user criteria
     *
     * @param JobCriteria $criteria
     * @return Collection<Job>
     * @throws RuntimeException If there's an API error
     */
    public function searchJobs(JobCriteria $criteria): Collection
    {
        try {
            // Build search query from criteria
            $query = $this->buildSearchQuery($criteria);

            // Search for jobs using Tavily
            $searchResults = $this->tavily->searchJobs($query, [
                'include_answer' => true,
                'max_results' => 20,
            ]);

            // Process and analyze results
            return $this->processSearchResults($searchResults, $criteria);
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Failed to search for jobs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new RuntimeException('Failed to search for jobs: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Build a search query from job criteria
     *
     * @param JobCriteria $criteria
     * @return string
     */
    private function buildSearchQuery(JobCriteria $criteria): string
    {
        $parts = [];

        if ($criteria->title) {
            $parts[] = $criteria->title;
        }

        if (!empty($criteria->keywords)) {
            $parts[] = implode(' ', $criteria->keywords);
        }

        if ($criteria->location) {
            $parts[] = 'in ' . $criteria->location;
        }

        if ($criteria->job_type) {
            $parts[] = $criteria->job_type;
        }

        if ($criteria->min_salary) {
            $parts[] = '$' . number_format($criteria->min_salary) . '+ salary';
        }

        return implode(' ', array_filter($parts)) ?: 'job openings';
    }

    /**
     * Process and analyze search results
     *
     * @param array $searchResults
     * @param JobCriteria $criteria
     * @return Collection<Job>
     */
    private function processSearchResults(array $searchResults, JobCriteria $criteria): Collection
    {
        $jobs = collect();

        foreach ($searchResults['results'] ?? [] as $result) {
            try {
                // Skip if not a job posting or missing required fields
                if (!$this->isValidJobPosting($result)) {
                    continue;
                }

                // Analyze job posting with Claude
                $analysis = $this->analyzeJobPosting($result, $criteria);

                // If the job matches criteria, create a Job model
                if ($analysis['matches_criteria']) {
                    $jobs->push($this->createJobFromAnalysis($result, $analysis, $criteria->user_id));
                }
            } catch (\Throwable $e) {
                // Log error and continue with next result
                Log::warning('Failed to process job posting', [
                    'result' => $result,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                continue;
            }
        }

        return $jobs;
    }

    /**
     * Check if a search result is a valid job posting
     *
     * @param array $result
     * @return bool
     */
    private function isValidJobPosting(array $result): bool
    {
        // Check for required fields
        if (!isset($result['title'], $result['url'], $result['content'])) {
            return false;
        }

        $title = strtolower($result['title']);
        $url = strtolower($result['url']);
        $content = strtolower($result['content']);

        // Check if URL is from a job board
        $jobDomains = [
            'linkedin.com/jobs',
            'indeed.com/job',
            'glassdoor.com/job',
            'careers.',
            'jobs.',
            '/job/',
            '/careers/'
        ];

        foreach ($jobDomains as $domain) {
            if (str_contains($url, $domain)) {
                return true;
            }
        }

        // Check if title contains job-related keywords
        $jobKeywords = ['job', 'career', 'position', 'opening', 'hiring', 'vacancy'];
        foreach ($jobKeywords as $keyword) {
            if (str_contains($title, $keyword)) {
                // Additional check to filter out blog posts and articles
                $blogKeywords = ['blog', 'article', 'news', 'about', 'tips', 'guide', 'how to'];
                foreach ($blogKeywords as $blogKeyword) {
                    if (str_contains($title, $blogKeyword)) {
                        return false;
                    }
                }
                return true;
            }
        }

        return false;
    }

    /**
     * Analyze a job posting using Claude
     *
     * @param array $result
     * @param JobCriteria $criteria
     * @return array
     * @throws RuntimeException If analysis fails
     */
    private function analyzeJobPosting(array $result, JobCriteria $criteria): array
    {
        $prompt = $this->buildAnalysisPrompt($result, $criteria);

        try {
            $analysis = $this->anthropic->generateText($prompt, [
                'temperature' => 0.2,
                'max_tokens' => 500,
                'system' => 'You are an expert job market analyst. Analyze job postings and extract key information accurately.'
            ]);

            try {
                $data = json_decode($analysis, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($data)) {
                    return $data;
                }
            } catch (\JsonException $e) {
                // Fall through to error handling
            }

            throw new RuntimeException('Failed to parse analysis response');
        } catch (\Throwable $e) {
            Log::error('Failed to analyze job posting', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'prompt' => $prompt
            ]);
            throw new RuntimeException('Failed to analyze job posting: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Build a prompt for job posting analysis
     *
     * @param array $result
     * @param JobCriteria $criteria
     * @return string
     */
    private function buildAnalysisPrompt(array $result, JobCriteria $criteria): string
    {
        return <<<PROMPT
        Analyze this job posting and compare it with the candidate's criteria. Return a JSON object with your analysis.

        Job Posting:
        Title: {$result['title']}
        Content: {$result['content']}
        URL: {$result['url']}

        Candidate's Criteria:
        - Title: {$criteria->title}
        - Keywords: {$this->formatList($criteria->keywords)}
        - Location: {$criteria->location}
        - Minimum Salary: {$criteria->min_salary}
        - Job Type: {$criteria->job_type}
        - Required Skills: {$this->formatList($criteria->required_skills)}
        - Preferred Skills: {$this->formatList($criteria->preferred_skills)}

        Analyze and return a JSON object with:
        {
            "matches_criteria": boolean,
            "reason": string,
            "extracted_info": {
                "title": string,
                "company": string,
                "location": string,
                "salary_min": number|null,
                "salary_max": number|null,
                "job_type": string|null,
                "required_skills": string[],
                "preferred_skills": string[],
                "description": string
            },
            "confidence_score": number (0-1)
        }
        PROMPT;
    }

    /**
     * Create a Job model from analysis results
     *
     * @param array $result
     * @param array $analysis
     * @param int $userId
     * @return Job
     */
    private function createJobFromAnalysis(array $result, array $analysis, int $userId): Job
    {
        $info = $analysis['extracted_info'];

        return Job::create([
            'user_id' => $userId,
            'title' => $info['title'],
            'company' => $info['company'],
            'location' => $info['location'],
            'description' => $info['description'],
            'job_link' => $result['url'],
            'salary_min' => $info['salary_min'],
            'salary_max' => $info['salary_max'],
            'job_type' => $info['job_type'],
            'required_skills' => $info['required_skills'],
            'preferred_skills' => $info['preferred_skills'],
            'status' => Job::STATUS_NEW,
        ]);
    }

    /**
     * Format a list for display in prompts
     *
     * @param array|null $items
     * @return string
     */
    private function formatList(?array $items): string
    {
        return implode(', ', array_filter($items ?? []));
    }
} 