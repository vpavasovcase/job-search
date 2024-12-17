<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // TODO: Replace with actual data from your models
        $data = [
            'foundJobsCount' => 0,
            'applicationsCount' => 0,
            'communicationsCount' => 0,
            'interviewsCount' => 0,
            'latestJobs' => [],
            'upcomingInterviews' => [],
            'pendingInstructionChanges' => []
        ];

        return view('dashboard.index', $data);
    }
}
