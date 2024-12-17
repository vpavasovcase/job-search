<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Job;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'job_id' => Job::factory(),
            'resume_id' => Resume::factory(),
            'cover_letter' => function (array $attributes) {
                $job = Job::find($attributes['job_id']);
                return $this->generateCoverLetter($job);
            },
            'status' => $this->faker->randomElement([
                Application::STATUS_DRAFT,
                Application::STATUS_SUBMITTED,
                Application::STATUS_UNDER_REVIEW,
                Application::STATUS_REJECTED,
                Application::STATUS_ACCEPTED,
                Application::STATUS_WITHDRAWN
            ]),
            'submitted_at' => $this->faker->optional(0.8)->dateTimeBetween('-30 days', 'now'),
            'metadata' => [
                'submission_platform' => $this->faker->randomElement(['Company Website', 'LinkedIn', 'Indeed', 'Email']),
                'follow_up_count' => $this->faker->numberBetween(0, 3),
                'last_interaction' => $this->faker->optional(0.7)->dateTimeBetween('-30 days', 'now')?->format('Y-m-d H:i:s'),
                'notes' => $this->faker->optional(0.6)->sentences(2, true)
            ],
        ];
    }

    protected function generateCoverLetter(Job $job): string
    {
        return "Dear Hiring Manager,\n\n" .
               "I am writing to express my strong interest in the {$job->title} position at {$job->company}. " .
               $this->faker->paragraph() . "\n\n" .
               $this->faker->paragraph() . "\n\n" .
               $this->faker->paragraph() . "\n\n" .
               "Thank you for considering my application.\n\n" .
               "Best regards,\n" .
               $this->faker->name();
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Application::STATUS_DRAFT,
            'submitted_at' => null,
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Application::STATUS_SUBMITTED,
            'submitted_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Application::STATUS_ACCEPTED,
            'submitted_at' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Application::STATUS_REJECTED,
            'submitted_at' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }
}
