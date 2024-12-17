<?php

namespace Database\Seeders;

use App\Models\AgentInstruction;
use App\Models\Application;
use App\Models\Communication;
use App\Models\Interview;
use App\Models\Job;
use App\Models\JobCriteria;
use App\Models\ProposedInstructionChange;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Factories\Factory;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create main test user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create additional users
        $users = User::factory(5)->create();
        $allUsers = $users->push($user);

        // Create resumes for each user
        $allUsers->each(function ($user) {
            $resumes = Resume::factory(mt_rand(1, 3))->create(['user_id' => $user->id]);
            
            // Create job criteria
            JobCriteria::factory(mt_rand(2, 4))->create(['user_id' => $user->id]);

            // Create jobs in various states
            $jobs = collect([
                Job::factory()->newJob()->create(['user_id' => $user->id]),
                Job::factory()->applied()->create(['user_id' => $user->id]),
                Job::factory()->interviewing()->create(['user_id' => $user->id]),
                Job::factory()->offered()->create(['user_id' => $user->id]),
                ...Job::factory(mt_rand(3, 7))->create(['user_id' => $user->id])
            ]);

            // Create applications for some jobs
            $jobs->each(function ($job) use ($user, $resumes) {
                if (in_array($job->status, [Job::STATUS_APPLIED, Job::STATUS_INTERVIEWING, Job::STATUS_OFFERED])) {
                    $application = Application::factory()->create([
                        'user_id' => $user->id,
                        'job_id' => $job->id,
                        'resume_id' => $resumes->random()->id
                    ]);

                    // Create communications for each application
                    Communication::factory(mt_rand(2, 5))->create([
                        'user_id' => $user->id,
                        'job_id' => $job->id,
                        'application_id' => $application->id
                    ]);

                    // Create interviews for jobs in interviewing status
                    if ($job->status === Job::STATUS_INTERVIEWING) {
                        Interview::factory(mt_rand(1, 3))->create([
                            'user_id' => $user->id,
                            'job_id' => $job->id
                        ]);
                    }
                }
            });

            // Create agent instructions
            $instructions = collect([
                AgentInstruction::factory()->jobSearch()->create(['user_id' => $user->id]),
                AgentInstruction::factory()->applicationDraft()->create(['user_id' => $user->id]),
                AgentInstruction::factory()->communication()->create(['user_id' => $user->id]),
                AgentInstruction::factory()->scheduling()->create(['user_id' => $user->id]),
                AgentInstruction::factory()->controller()->create(['user_id' => $user->id])
            ]);

            // Create proposed changes for some instructions
            $instructions->each(function ($instruction) {
                if (mt_rand(1, 100) <= 70) { // 70% chance of having proposed changes
                    ProposedInstructionChange::factory(mt_rand(1, 3))->create([
                        'agent_instruction_id' => $instruction->id
                    ]);
                }
            });
        });
    }
}
