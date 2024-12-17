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
        Schema::create('proposed_instruction_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_instruction_id')->constrained()->onDelete('cascade');
            $table->text('current_instructions');
            $table->text('proposed_instructions');
            $table->text('reason_for_change');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('feedback')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposed_instruction_changes');
    }
};
