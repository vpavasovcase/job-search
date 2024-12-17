<?php

namespace Database\Factories;

use App\Models\JobCriteria;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobCriteria>
 */
class JobCriteriaFactory extends Factory
{
    protected $model = JobCriteria::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $techSkills = ['PHP', 'Laravel', 'JavaScript', 'React', 'Vue.js', 'Node.js', 'Python', 'Java', 'Docker', 'AWS', 'SQL', 'MongoDB'];
        $softSkills = ['Communication', 'Leadership', 'Problem Solving', 'Team Work', 'Agile', 'Time Management'];

        return [
            'user_id' => User::factory(),
            'title' => $this->faker->jobTitle() . ' Search Criteria',
            'keywords' => $this->faker->randomElements($techSkills, mt_rand(3, 6)),
            'location' => $this->faker->city() . ', ' . $this->faker->stateAbbr(),
            'min_salary' => $this->faker->numberBetween(60000, 150000),
            'job_type' => $this->faker->randomElement(['full-time', 'part-time', 'contract', 'freelance']),
            'required_skills' => $this->faker->randomElements($techSkills, mt_rand(2, 4)),
            'preferred_skills' => $this->faker->randomElements(array_merge($techSkills, $softSkills), mt_rand(3, 5)),
            'additional_requirements' => $this->faker->optional(0.7)->paragraph(),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
        ];
    }
}
