<?php

namespace Database\Factories;

use App\Models\Resume;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Resume>
 */
class ResumeFactory extends Factory
{
    protected $model = Resume::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->words(3, true) . ' Resume',
            'file_path' => 'resumes/' . $this->faker->uuid() . '.pdf',
            'metadata' => [
                'last_updated' => $this->faker->dateTimeThisYear(),
                'skills' => $this->faker->words(mt_rand(5, 10)),
                'education' => [
                    [
                        'degree' => $this->faker->randomElement(['BS', 'BA', 'MS', 'PhD']),
                        'field' => $this->faker->randomElement(['Computer Science', 'Software Engineering', 'Information Technology', 'Data Science']),
                        'school' => $this->faker->company(),
                        'year' => $this->faker->year()
                    ]
                ],
                'experience_years' => $this->faker->numberBetween(1, 15)
            ],
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }
}
