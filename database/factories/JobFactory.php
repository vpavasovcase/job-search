<?php

namespace Database\Factories;

use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Job>
 */
class JobFactory extends Factory
{
    protected $model = Job::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $techSkills = ['PHP', 'Laravel', 'JavaScript', 'React', 'Vue.js', 'Node.js', 'Python', 'Java', 'Docker', 'AWS', 'SQL', 'MongoDB'];
        $softSkills = ['Communication', 'Leadership', 'Problem Solving', 'Team Work', 'Agile', 'Time Management'];
        $minSalary = $this->faker->numberBetween(60000, 150000);
        
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->jobTitle(),
            'company' => $this->faker->company(),
            'location' => $this->faker->city() . ', ' . $this->faker->stateAbbr(),
            'description' => $this->faker->paragraphs(3, true),
            'job_link' => $this->faker->url(),
            'salary_min' => $minSalary,
            'salary_max' => $this->faker->optional(0.7)->numberBetween($minSalary, $minSalary + 50000),
            'job_type' => $this->faker->randomElement(['full-time', 'part-time', 'contract', 'freelance']),
            'required_skills' => $this->faker->randomElements($techSkills, mt_rand(2, 4)),
            'preferred_skills' => $this->faker->randomElements(array_merge($techSkills, $softSkills), mt_rand(3, 5)),
            'status' => $this->faker->randomElement([
                Job::STATUS_NEW,
                Job::STATUS_APPLIED,
                Job::STATUS_INTERVIEWING,
                Job::STATUS_OFFERED,
                Job::STATUS_REJECTED,
                Job::STATUS_ACCEPTED,
                Job::STATUS_DECLINED
            ]),
        ];
    }

    public function newJob(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Job::STATUS_NEW,
        ]);
    }

    public function applied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Job::STATUS_APPLIED,
        ]);
    }

    public function interviewing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Job::STATUS_INTERVIEWING,
        ]);
    }

    public function offered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Job::STATUS_OFFERED,
        ]);
    }
}
