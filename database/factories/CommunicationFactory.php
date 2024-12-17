<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Communication;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Communication>
 */
class CommunicationFactory extends Factory
{
    protected $model = Communication::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isIncoming = $this->faker->boolean();
        $type = $this->faker->randomElement([
            Communication::TYPE_EMAIL,
            Communication::TYPE_PHONE,
            Communication::TYPE_VIDEO,
            Communication::TYPE_IN_PERSON,
            Communication::TYPE_OTHER
        ]);

        return [
            'user_id' => User::factory(),
            'job_id' => Job::factory(),
            'application_id' => Application::factory(),
            'type' => $type,
            'direction' => $isIncoming ? Communication::DIRECTION_INCOMING : Communication::DIRECTION_OUTGOING,
            'content' => function (array $attributes) use ($type, $isIncoming) {
                $job = Job::find($attributes['job_id']);
                return $this->generateContent($type, $isIncoming, $job);
            },
            'status' => $this->faker->randomElement([
                Communication::STATUS_DRAFT,
                Communication::STATUS_SCHEDULED,
                Communication::STATUS_SENT,
                Communication::STATUS_DELIVERED,
                Communication::STATUS_READ,
                Communication::STATUS_FAILED
            ]),
            'sent_at' => $this->faker->optional(0.9)->dateTimeBetween('-30 days', 'now'),
            'metadata' => [
                'duration' => $type === Communication::TYPE_PHONE ? $this->faker->numberBetween(5, 30) : null,
                'platform' => $type === Communication::TYPE_VIDEO ? $this->faker->randomElement(['Zoom', 'Google Meet', 'Microsoft Teams']) : null,
                'contact_name' => $this->faker->name(),
                'contact_title' => $this->faker->jobTitle(),
                'follow_up_required' => $this->faker->boolean(30),
            ],
        ];
    }

    protected function generateContent(string $type, bool $isIncoming, Job $job): string
    {
        if ($type === Communication::TYPE_EMAIL) {
            return $this->generateEmailContent($isIncoming, $job);
        }

        if ($type === Communication::TYPE_PHONE || $type === Communication::TYPE_VIDEO) {
            return $this->generateCallNotes($isIncoming);
        }

        return $this->faker->paragraph();
    }

    protected function generateEmailContent(bool $isIncoming, Job $job): string
    {
        if ($isIncoming) {
            return "Subject: RE: {$job->title} Position\n\n" .
                   "Dear " . $this->faker->firstName() . ",\n\n" .
                   $this->faker->paragraph() . "\n\n" .
                   $this->faker->paragraph() . "\n\n" .
                   "Best regards,\n" .
                   $this->faker->name() . "\n" .
                   $this->faker->jobTitle() . "\n" .
                   $job->company;
        }

        return "Subject: {$job->title} Position\n\n" .
               "Dear Hiring Manager,\n\n" .
               $this->faker->paragraph() . "\n\n" .
               $this->faker->paragraph() . "\n\n" .
               "Best regards,\n" .
               $this->faker->name();
    }

    protected function generateCallNotes(bool $isIncoming): string
    {
        $notes = [
            "Call Summary:\n",
            "- Duration: " . $this->faker->numberBetween(5, 30) . " minutes\n",
            "- Main Points Discussed:\n"
        ];

        for ($i = 0; $i < $this->faker->numberBetween(2, 5); $i++) {
            $notes[] = "  * " . $this->faker->sentence() . "\n";
        }

        $notes[] = "\nNext Steps:\n";
        $notes[] = "- " . $this->faker->sentence();

        return implode('', $notes);
    }

    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Communication::TYPE_EMAIL,
        ]);
    }

    public function phone(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Communication::TYPE_PHONE,
        ]);
    }

    public function incoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => Communication::DIRECTION_INCOMING,
        ]);
    }

    public function outgoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => Communication::DIRECTION_OUTGOING,
        ]);
    }
}
