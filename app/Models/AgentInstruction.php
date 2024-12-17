<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AgentInstruction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'agent_type',
        'instructions',
        'configuration',
        'is_active',
    ];

    protected $casts = [
        'configuration' => 'array',
        'is_active' => 'boolean',
    ];

    // Agent type constants
    const TYPE_JOB_SEARCH = 'JobSearchAgent';
    const TYPE_APPLICATION_DRAFT = 'ApplicationDraftAgent';
    const TYPE_COMMUNICATION = 'CommunicationAgent';
    const TYPE_SCHEDULING = 'SchedulingAgent';
    const TYPE_CONTROLLER = 'ControllerAgent';

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function proposedChanges()
    {
        return $this->hasMany(ProposedInstructionChange::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('agent_type', $type);
    }

    public function scopeWithPendingChanges(Builder $query): Builder
    {
        return $query->whereHas('proposedChanges', function ($q) {
            $q->where('status', ProposedInstructionChange::STATUS_PENDING);
        });
    }

    // Methods
    public function deactivate(): bool
    {
        $this->is_active = false;
        return $this->save();
    }

    public function activate(): bool
    {
        $this->is_active = true;
        return $this->save();
    }

    public function proposeChange(string $newInstructions, string $reason): ProposedInstructionChange
    {
        return $this->proposedChanges()->create([
            'current_instructions' => $this->instructions,
            'proposed_instructions' => $newInstructions,
            'reason_for_change' => $reason,
            'status' => ProposedInstructionChange::STATUS_PENDING,
        ]);
    }

    public function updateInstructions(string $newInstructions): bool
    {
        $this->instructions = $newInstructions;
        return $this->save();
    }

    public function updateConfiguration(array $config): bool
    {
        $this->configuration = array_merge($this->configuration ?? [], $config);
        return $this->save();
    }

    // Accessors
    public function getHasPendingChangesAttribute(): bool
    {
        return $this->proposedChanges()
            ->where('status', ProposedInstructionChange::STATUS_PENDING)
            ->exists();
    }

    public function getLastUpdateAttribute(): ?string
    {
        return $this->updated_at ? $this->updated_at->diffForHumans() : null;
    }
}
