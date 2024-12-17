<?php

namespace App\Services\Agents;

use App\Models\Job;
use App\Models\Resume;
use App\Models\Application;
use App\Services\AnthropicClient;
use Illuminate\Support\Str;

class ApplicationDraftAgent
{
    public function __construct(
        private AnthropicClient $anthropic
    ) {}

    /**
     * Create a draft application for a job
     *
     * @param Job $job
     * @param Resume $resume
     * @param array $instructions
     * @return Application
     */
    public function createApplicationDraft(Job $job, Resume $resume, array $instructions): Application
    {
        $coverLetter = $this->generateCoverLetter($job, $resume, $instructions);

        return Application::create([
            'user_id' => $resume->user_id,
            'job_id' => $job->id,
            'resume_id' => $resume->id,
            'cover_letter' => $coverLetter,
            'status' => Application::STATUS_DRAFT,
            'metadata' => [
                'generated_at' => now(),
                'instruction_context' => $instructions,
            ]
        ]);
    }

    /**
     * Generate a cover letter using the Anthropic API
     *
     * @param Job $job
     * @param Resume $resume
     * @param array $instructions
     * @return string
     */
    private function generateCoverLetter(Job $job, Resume $resume, array $instructions): string
    {
        $prompt = $this->buildCoverLetterPrompt($job, $resume, $instructions);
        
        return $this->anthropic->generateText($prompt, [
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ]);
    }

    /**
     * Build the prompt for cover letter generation
     *
     * @param Job $job
     * @param Resume $resume
     * @param array $instructions
     * @return string
     */
    private function buildCoverLetterPrompt(Job $job, Resume $resume, array $instructions): string
    {
        $resumeSkills = $resume->metadata['skills'] ?? [];
        $jobSkills = array_merge($job->required_skills ?? [], $job->preferred_skills ?? []);
        $matchingSkills = array_intersect($resumeSkills, $jobSkills);

        return <<<PROMPT
        Write a professional cover letter for a {$job->title} position at {$job->company}. 
        
        Job Details:
        - Title: {$job->title}
        - Company: {$job->company}
        - Description: {$job->description}
        - Required Skills: {$this->formatList($job->required_skills)}
        - Preferred Skills: {$this->formatList($job->preferred_skills)}
        
        Candidate Background:
        - Experience: {$resume->metadata['experience_years']} years
        - Education: {$this->formatEducation($resume->metadata['education'] ?? [])}
        - Relevant Skills: {$this->formatList($matchingSkills)}
        
        Additional Instructions:
        {$this->formatInstructions($instructions)}
        
        The cover letter should:
        1. Be professional and engaging
        2. Highlight matching skills and relevant experience
        3. Show enthusiasm for the role and company
        4. Demonstrate understanding of the company's needs
        5. Include specific examples of relevant achievements
        6. Be concise but comprehensive
        
        Format the letter with proper spacing and paragraphs.
        PROMPT;
    }

    /**
     * Format a list of items for the prompt
     *
     * @param array $items
     * @return string
     */
    private function formatList(array $items): string
    {
        return implode(', ', array_filter($items));
    }

    /**
     * Format education information for the prompt
     *
     * @param array $education
     * @return string
     */
    private function formatEducation(array $education): string
    {
        return collect($education)
            ->map(fn($edu) => "{$edu['degree']} in {$edu['field']} from {$edu['school']} ({$edu['year']})")
            ->implode('; ');
    }

    /**
     * Format additional instructions for the prompt
     *
     * @param array $instructions
     * @return string
     */
    private function formatInstructions(array $instructions): string
    {
        return collect($instructions)
            ->map(fn($instruction) => "- " . trim($instruction))
            ->implode("\n");
    }
} 