<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Job Search Status -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">{{ __('Job Search Status') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="text-sm text-blue-600">Found Jobs</div>
                            <div class="text-2xl font-bold">{{ $foundJobsCount ?? 0 }}</div>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="text-sm text-green-600">Applications</div>
                            <div class="text-2xl font-bold">{{ $applicationsCount ?? 0 }}</div>
                        </div>
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <div class="text-sm text-yellow-600">Communications</div>
                            <div class="text-2xl font-bold">{{ $communicationsCount ?? 0 }}</div>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <div class="text-sm text-purple-600">Interviews</div>
                            <div class="text-2xl font-bold">{{ $interviewsCount ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Latest Jobs -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">{{ __('Latest Jobs') }}</h3>
                        <a href="#" class="text-blue-600 hover:text-blue-800">View All</a>
                    </div>
                    @if(isset($latestJobs) && count($latestJobs) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($latestJobs ?? [] as $job)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $job->title }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $job->company }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $job->location }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                {{ $job->status }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500">No jobs found yet.</p>
                    @endif
                </div>
            </div>

            <!-- Upcoming Interviews -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">{{ __('Upcoming Interviews') }}</h3>
                        <a href="#" class="text-blue-600 hover:text-blue-800">View All</a>
                    </div>
                    @if(isset($upcomingInterviews) && count($upcomingInterviews) > 0)
                        <div class="space-y-4">
                            @foreach($upcomingInterviews ?? [] as $interview)
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <h4 class="font-medium">{{ $interview->company }}</h4>
                                    <p class="text-sm text-gray-600">{{ $interview->position }}</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium">{{ $interview->date }}</div>
                                    <div class="text-sm text-gray-600">{{ $interview->time }}</div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500">No upcoming interviews scheduled.</p>
                    @endif
                </div>
            </div>

            <!-- Agent Instructions Updates -->
            @if(isset($pendingInstructionChanges) && count($pendingInstructionChanges) > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">{{ __('Pending Agent Instruction Updates') }}</h3>
                    <div class="space-y-4">
                        @foreach($pendingInstructionChanges as $change)
                        <div class="border rounded-lg p-4">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-medium">{{ $change->agent_type }}</h4>
                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Pending Approval</span>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">{{ $change->description }}</p>
                            <div class="flex space-x-2">
                                <form action="{{ route('agent-instructions.approve-changes', $change->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-md text-sm hover:bg-green-600">
                                        Approve
                                    </button>
                                </form>
                                <button class="bg-red-500 text-white px-4 py-2 rounded-md text-sm hover:bg-red-600">
                                    Reject
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout> 