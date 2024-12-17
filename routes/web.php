<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\JobCriteriaController;
use App\Http\Controllers\AgentInstructionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Resumes
    Route::get('/resumes', [ResumeController::class, 'index'])->name('resumes.index');
    Route::post('/resumes', [ResumeController::class, 'store'])->name('resumes.store');
    Route::delete('/resumes/{resume}', [ResumeController::class, 'destroy'])->name('resumes.destroy');
    
    // Job Criteria
    Route::resource('job-criteria', JobCriteriaController::class);
    
    // Agent Instructions
    Route::get('/agent-instructions', [AgentInstructionController::class, 'index'])->name('agent-instructions.index');
    Route::get('/agent-instructions/{type}', [AgentInstructionController::class, 'show'])->name('agent-instructions.show');
    Route::put('/agent-instructions/{type}', [AgentInstructionController::class, 'update'])->name('agent-instructions.update');
    Route::get('/agent-instructions/{type}/proposed-changes', [AgentInstructionController::class, 'proposedChanges'])
        ->name('agent-instructions.proposed-changes');
    Route::post('/agent-instructions/{type}/approve-changes', [AgentInstructionController::class, 'approveChanges'])
        ->name('agent-instructions.approve-changes');
});

// Profile routes (added by Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
