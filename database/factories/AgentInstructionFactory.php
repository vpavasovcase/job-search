<?php

namespace Database\Factories;

use App\Models\AgentInstruction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgentInstruction>
 */
class AgentInstructionFactory extends Factory
{
    protected $model = AgentInstruction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $agentType = $this->faker->randomElement([
            AgentInstruction::TYPE_JOB_SEARCH,
            AgentInstruction::TYPE_APPLICATION_DRAFT,
            AgentInstruction::TYPE_COMMUNICATION,
            AgentInstruction::TYPE_SCHEDULING,
            AgentInstruction::TYPE_CONTROLLER
        ]);

        return [
            'user_id' => User::factory(),
            'agent_type' => $agentType,
            'instructions' => $this->generateInstructions($agentType),
            'configuration' => $this->generateConfiguration($agentType),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
        ];
    }

    protected function generateInstructions(string $agentType): string
    {
        switch ($agentType) {
            case AgentInstruction::TYPE_JOB_SEARCH:
                return $this->generateJobSearchInstructions();
            case AgentInstruction::TYPE_APPLICATION_DRAFT:
                return $this->generateApplicationDraftInstructions();
            case AgentInstruction::TYPE_COMMUNICATION:
                return $this->generateCommunicationInstructions();
            case AgentInstruction::TYPE_SCHEDULING:
                return $this->generateSchedulingInstructions();
            case AgentInstruction::TYPE_CONTROLLER:
                return $this->generateControllerInstructions();
            default:
                return $this->faker->paragraph();
        }
    }

    protected function generateConfiguration(string $agentType): array
    {
        $baseConfig = [
            'max_retries' => $this->faker->numberBetween(3, 5),
            'timeout_seconds' => $this->faker->numberBetween(30, 120),
            'notification_enabled' => $this->faker->boolean(80),
        ];

        switch ($agentType) {
            case AgentInstruction::TYPE_JOB_SEARCH:
                return array_merge($baseConfig, [
                    'search_interval_hours' => $this->faker->numberBetween(4, 24),
                    'job_boards' => $this->faker->randomElements(['LinkedIn', 'Indeed', 'Glassdoor', 'AngelList', 'Stack Overflow'], $this->faker->numberBetween(2, 4)),
                    'max_jobs_per_search' => $this->faker->numberBetween(10, 30),
                ]);
            case AgentInstruction::TYPE_APPLICATION_DRAFT:
                return array_merge($baseConfig, [
                    'use_ai_enhancement' => $this->faker->boolean(90),
                    'max_cover_letter_words' => $this->faker->numberBetween(300, 500),
                    'include_portfolio' => $this->faker->boolean(60),
                ]);
            case AgentInstruction::TYPE_COMMUNICATION:
                return array_merge($baseConfig, [
                    'auto_follow_up' => $this->faker->boolean(70),
                    'follow_up_interval_days' => $this->faker->numberBetween(5, 14),
                    'max_follow_ups' => $this->faker->numberBetween(2, 4),
                ]);
            case AgentInstruction::TYPE_SCHEDULING:
                return array_merge($baseConfig, [
                    'calendar_integration' => $this->faker->randomElement(['Google Calendar', 'Outlook', 'iCal']),
                    'reminder_hours_before' => $this->faker->numberBetween(24, 48),
                    'working_hours' => [
                        'start' => '09:00',
                        'end' => '17:00',
                        'timezone' => 'UTC'
                    ],
                ]);
            case AgentInstruction::TYPE_CONTROLLER:
                return array_merge($baseConfig, [
                    'improvement_interval_days' => $this->faker->numberBetween(7, 30),
                    'max_concurrent_tasks' => $this->faker->numberBetween(3, 8),
                    'performance_metrics_enabled' => $this->faker->boolean(90),
                ]);
            default:
                return $baseConfig;
        }
    }

    protected function generateJobSearchInstructions(): string
    {
        return "Job Search Agent Instructions:\n\n" .
               "1. Search Criteria:\n" .
               "   - Use provided job criteria for matching\n" .
               "   - Focus on " . $this->faker->randomElement(['remote', 'hybrid', 'on-site']) . " positions\n" .
               "   - Prioritize jobs posted within last " . $this->faker->numberBetween(7, 30) . " days\n\n" .
               "2. Filtering:\n" .
               "   - Match at least " . $this->faker->numberBetween(60, 90) . "% of required skills\n" .
               "   - Salary range within specified bounds\n" .
               "   - Company size: " . $this->faker->randomElement(['startup', 'small-medium', 'enterprise']) . "\n\n" .
               "3. Actions:\n" .
               "   - Save matching jobs for review\n" .
               "   - Generate initial job assessment\n" .
               "   - Flag high-priority matches\n";
    }

    protected function generateApplicationDraftInstructions(): string
    {
        return "Application Draft Agent Instructions:\n\n" .
               "1. Cover Letter Generation:\n" .
               "   - Analyze job description for key requirements\n" .
               "   - Match experience from resume\n" .
               "   - Highlight " . $this->faker->numberBetween(3, 5) . " relevant achievements\n\n" .
               "2. Customization:\n" .
               "   - Include company-specific research\n" .
               "   - Reference company values and culture\n" .
               "   - Maintain professional tone\n\n" .
               "3. Review Process:\n" .
               "   - Check for keyword optimization\n" .
               "   - Ensure all requirements are addressed\n" .
               "   - Proofread for errors\n";
    }

    protected function generateCommunicationInstructions(): string
    {
        return "Communication Agent Instructions:\n\n" .
               "1. Email Management:\n" .
               "   - Monitor for employer responses\n" .
               "   - Draft follow-up emails\n" .
               "   - Maintain professional communication log\n\n" .
               "2. Response Handling:\n" .
               "   - Acknowledge within " . $this->faker->numberBetween(2, 4) . " hours\n" .
               "   - Schedule interviews when requested\n" .
               "   - Forward urgent matters\n\n" .
               "3. Follow-up Strategy:\n" .
               "   - Initial follow-up after " . $this->faker->numberBetween(5, 10) . " days\n" .
               "   - Maximum " . $this->faker->numberBetween(2, 4) . " follow-ups\n" .
               "   - Maintain engagement records\n";
    }

    protected function generateSchedulingInstructions(): string
    {
        return "Scheduling Agent Instructions:\n\n" .
               "1. Interview Coordination:\n" .
               "   - Check calendar availability\n" .
               "   - Propose " . $this->faker->numberBetween(2, 4) . " time slots\n" .
               "   - Send confirmations\n\n" .
               "2. Preparation:\n" .
               "   - Send reminders " . $this->faker->numberBetween(24, 48) . " hours before\n" .
               "   - Include interview details\n" .
               "   - Attach relevant documents\n\n" .
               "3. Follow-up:\n" .
               "   - Confirm attendance\n" .
               "   - Handle rescheduling requests\n" .
               "   - Update calendar events\n";
    }

    protected function generateControllerInstructions(): string
    {
        return "Controller Agent Instructions:\n\n" .
               "1. Process Oversight:\n" .
               "   - Monitor agent performance\n" .
               "   - Optimize task distribution\n" .
               "   - Identify improvement areas\n\n" .
               "2. Coordination:\n" .
               "   - Synchronize agent activities\n" .
               "   - Resolve conflicts\n" .
               "   - Maintain process flow\n\n" .
               "3. Improvement:\n" .
               "   - Analyze success metrics\n" .
               "   - Propose instruction updates\n" .
               "   - Implement approved changes\n";
    }

    public function jobSearch(): static
    {
        return $this->state(fn (array $attributes) => [
            'agent_type' => AgentInstruction::TYPE_JOB_SEARCH,
        ]);
    }

    public function applicationDraft(): static
    {
        return $this->state(fn (array $attributes) => [
            'agent_type' => AgentInstruction::TYPE_APPLICATION_DRAFT,
        ]);
    }

    public function communication(): static
    {
        return $this->state(fn (array $attributes) => [
            'agent_type' => AgentInstruction::TYPE_COMMUNICATION,
        ]);
    }

    public function scheduling(): static
    {
        return $this->state(fn (array $attributes) => [
            'agent_type' => AgentInstruction::TYPE_SCHEDULING,
        ]);
    }

    public function controller(): static
    {
        return $this->state(fn (array $attributes) => [
            'agent_type' => AgentInstruction::TYPE_CONTROLLER,
        ]);
    }
}
