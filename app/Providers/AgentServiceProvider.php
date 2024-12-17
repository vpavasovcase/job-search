<?php

namespace App\Providers;

use App\Services\Agents\JobSearchAgent;
use App\Services\Agents\ApplicationDraftAgent;
use App\Services\Agents\CommunicationAgent;
use App\Services\Agents\SchedulingAgent;
use App\Services\Agents\ControllerAgent;
use Illuminate\Support\ServiceProvider;

class AgentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(JobSearchAgent::class);
        $this->app->singleton(ApplicationDraftAgent::class);
        $this->app->singleton(CommunicationAgent::class);
        $this->app->singleton(SchedulingAgent::class);
        $this->app->singleton(ControllerAgent::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
} 