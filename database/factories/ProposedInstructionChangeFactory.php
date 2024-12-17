<?php

namespace Database\Factories;

use App\Models\AgentInstruction;
use App\Models\ProposedInstructionChange;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProposedInstructionChange>
 */
class ProposedInstructionChangeFactory extends Factory
{
    protected $model = ProposedInstructionChange::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $instruction = AgentInstruction::factory()->create();
        $currentInstructions = $instruction->instructions;
        
        return [
            'agent_instruction_id' => $instruction->id,
            'current_instructions' => $currentInstructions,
            'proposed_instructions' => $this->generateProposedInstructions($instruction->agent_type, $currentInstructions),
            'reason_for_change' => $this->generateChangeReason($instruction->agent_type),
            'status' => $this->faker->randomElement([
                ProposedInstructionChange::STATUS_PENDING,
                ProposedInstructionChange::STATUS_APPROVED,
                ProposedInstructionChange::STATUS_REJECTED
            ]),
            'feedback' => $this->faker->optional(0.7)->paragraph(),
            'reviewed_at' => $this->faker->optional(0.6)->dateTimeBetween('-30 days', 'now'),
        ];
    }

    protected function generateProposedInstructions(string $agentType, string $currentInstructions): string
    {
        // Simulate AI-generated improvements to the instructions
        $improvements = [
            'Added efficiency optimization steps',
            'Enhanced error handling procedures',
            'Improved success rate metrics',
            'Updated communication protocols',
            'Refined decision-making criteria',
            'Added new best practices',
            'Optimized resource utilization',
            'Enhanced user interaction patterns'
        ];

        $selectedImprovements = $this->faker->randomElements($improvements, $this->faker->numberBetween(2, 4));
        
        return $currentInstructions . "\n\nProposed Improvements:\n" .
               implode("\n", array_map(fn($imp) => "- $imp", $selectedImprovements));
    }

    protected function generateChangeReason(string $agentType): string
    {
        $reasons = [
            'Performance Optimization' => [
                'Reduced average response time by ' . $this->faker->numberBetween(20, 50) . '%',
                'Improved success rate by ' . $this->faker->numberBetween(15, 40) . '%',
                'Enhanced resource utilization efficiency'
            ],
            'Error Reduction' => [
                'Addressed common failure patterns',
                'Implemented robust error handling',
                'Added validation checks'
            ],
            'User Experience' => [
                'Improved interaction clarity',
                'Enhanced feedback mechanisms',
                'Streamlined process flow'
            ],
            'Process Improvement' => [
                'Optimized workflow sequence',
                'Added parallel processing capabilities',
                'Reduced redundant operations'
            ]
        ];

        $category = $this->faker->randomElement(array_keys($reasons));
        $specificReason = $this->faker->randomElement($reasons[$category]);

        return "Category: $category\n" .
               "Specific Improvement: $specificReason\n\n" .
               "Analysis:\n" .
               "- Current performance metrics indicate room for improvement\n" .
               "- Proposed changes based on " . $this->faker->numberBetween(100, 1000) . " operation samples\n" .
               "- Expected improvement: " . $this->faker->numberBetween(15, 45) . "% in overall efficiency";
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProposedInstructionChange::STATUS_PENDING,
            'reviewed_at' => null,
            'feedback' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProposedInstructionChange::STATUS_APPROVED,
            'reviewed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'feedback' => 'Changes approved. Implementation authorized.',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProposedInstructionChange::STATUS_REJECTED,
            'reviewed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'feedback' => $this->faker->randomElement([
                'Proposed changes too aggressive. Please revise.',
                'Insufficient evidence for improvement. Need more data.',
                'Current instructions performing adequately. Hold changes.',
                'Changes may introduce unnecessary complexity.',
            ]),
        ]);
    }
}
