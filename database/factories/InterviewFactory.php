<?php

namespace Database\Factories;

use App\Models\Interview;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Interview>
 */
class InterviewFactory extends Factory
{
    protected $model = Interview::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement([
            Interview::TYPE_PHONE,
            Interview::TYPE_VIDEO,
            Interview::TYPE_ONSITE,
            Interview::TYPE_TECHNICAL,
            Interview::TYPE_BEHAVIORAL
        ]);

        return [
            'user_id' => User::factory(),
            'job_id' => Job::factory(),
            'type' => $type,
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+30 days'),
            'duration_minutes' => $this->faker->randomElement([30, 45, 60, 90, 120]),
            'location' => $this->generateLocation($type),
            'notes' => $this->generateNotes($type),
            'status' => $this->faker->randomElement([
                Interview::STATUS_SCHEDULED,
                Interview::STATUS_CONFIRMED,
                Interview::STATUS_COMPLETED,
                Interview::STATUS_CANCELLED,
                Interview::STATUS_RESCHEDULED,
                Interview::STATUS_NO_SHOW
            ]),
            'metadata' => [
                'interviewer_name' => $this->faker->name(),
                'interviewer_title' => $this->faker->jobTitle(),
                'preparation_materials' => $this->generatePreparationMaterials($type),
                'round_number' => $this->faker->numberBetween(1, 4),
                'follow_up_required' => $this->faker->boolean(30),
            ],
        ];
    }

    protected function generateLocation(string $type): string
    {
        switch ($type) {
            case Interview::TYPE_PHONE:
                return $this->faker->phoneNumber();
            case Interview::TYPE_VIDEO:
                return $this->faker->randomElement([
                    'https://zoom.us/j/' . $this->faker->numberBetween(1000000000, 9999999999),
                    'https://meet.google.com/' . $this->faker->lexify('???-????-???'),
                    'https://teams.microsoft.com/l/' . $this->faker->uuid
                ]);
            case Interview::TYPE_ONSITE:
                return $this->faker->address();
            default:
                return $this->faker->randomElement([
                    $this->faker->url(),
                    $this->faker->address(),
                    'Remote - link to be provided'
                ]);
        }
    }

    protected function generateNotes(string $type): string
    {
        $notes = [
            "Interview Details:\n",
            "- Format: " . ucfirst($type) . "\n",
            "- Duration: " . $this->faker->randomElement([30, 45, 60, 90, 120]) . " minutes\n",
            "\nPreparation Notes:\n"
        ];

        for ($i = 0; $i < $this->faker->numberBetween(2, 4); $i++) {
            $notes[] = "- " . $this->faker->sentence() . "\n";
        }

        if ($type === Interview::TYPE_TECHNICAL) {
            $notes[] = "\nTechnical Focus Areas:\n";
            for ($i = 0; $i < 3; $i++) {
                $notes[] = "- " . $this->faker->randomElement([
                    'Algorithm complexity analysis',
                    'System design principles',
                    'Database optimization',
                    'API design',
                    'Code refactoring',
                    'Testing strategies'
                ]) . "\n";
            }
        }

        return implode('', $notes);
    }

    protected function generatePreparationMaterials(string $type): array
    {
        $materials = [];

        if ($type === Interview::TYPE_TECHNICAL) {
            $materials['technical_topics'] = $this->faker->randomElements([
                'Data Structures',
                'Algorithms',
                'System Design',
                'Database Design',
                'API Development',
                'Testing',
                'DevOps',
                'Security'
            ], $this->faker->numberBetween(3, 5));
        }

        if ($type === Interview::TYPE_BEHAVIORAL) {
            $materials['suggested_scenarios'] = $this->faker->randomElements([
                'Team Conflict Resolution',
                'Project Management',
                'Leadership Experience',
                'Technical Challenge',
                'Client Interaction',
                'Process Improvement'
            ], $this->faker->numberBetween(2, 4));
        }

        return $materials;
    }

    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+14 days'),
            'status' => Interview::STATUS_SCHEDULED,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
            'status' => Interview::STATUS_COMPLETED,
        ]);
    }

    public function technical(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Interview::TYPE_TECHNICAL,
        ]);
    }

    public function behavioral(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Interview::TYPE_BEHAVIORAL,
        ]);
    }
}
