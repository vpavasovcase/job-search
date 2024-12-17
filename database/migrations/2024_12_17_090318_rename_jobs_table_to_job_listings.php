<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, drop the foreign keys to avoid conflicts
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['job_id']);
        });

        Schema::table('communications', function (Blueprint $table) {
            $table->dropForeign(['job_id']);
        });

        Schema::table('interviews', function (Blueprint $table) {
            $table->dropForeign(['job_id']);
        });

        // Create the new job_listings table
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('company');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('job_link');
            $table->decimal('salary_min', 10, 2)->nullable();
            $table->decimal('salary_max', 10, 2)->nullable();
            $table->string('job_type')->nullable();
            $table->json('required_skills')->nullable();
            $table->json('preferred_skills')->nullable();
            $table->string('status')->default('new');
            $table->timestamps();
        });

        // Add new foreign keys referencing job_listings
        Schema::table('applications', function (Blueprint $table) {
            $table->foreign('job_id')->references('id')->on('job_listings')->onDelete('cascade');
        });

        Schema::table('communications', function (Blueprint $table) {
            $table->foreign('job_id')->references('id')->on('job_listings')->nullOnDelete();
        });

        Schema::table('interviews', function (Blueprint $table) {
            $table->foreign('job_id')->references('id')->on('job_listings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys first
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['job_id']);
        });

        Schema::table('communications', function (Blueprint $table) {
            $table->dropForeign(['job_id']);
        });

        Schema::table('interviews', function (Blueprint $table) {
            $table->dropForeign(['job_id']);
        });

        // Drop the job_listings table
        Schema::dropIfExists('job_listings');
    }
};
