<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ProposedInstructionChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_instruction_id',
        'current_instructions',
        'proposed_instructions',
        'reason_for_change',
        'status',
        'feedback',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // Relationships
    public function agentInstruction()
    {
        return $this->belongsTo(AgentInstruction::class);
    }

    // Scopes
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeReviewed(Builder $query): Builder
    {
        return $query->whereNotNull('reviewed_at');
    }

    public function scopeNotReviewed(Builder $query): Builder
    {
        return $query->whereNull('reviewed_at');
    }

    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Methods
    public function approve(?string $feedback = null): bool
    {
        if ($this->status === self::STATUS_PENDING) {
            $this->status = self::STATUS_APPROVED;
            $this->feedback = $feedback;
            $this->reviewed_at = now();
            
            if ($this->save()) {
                return $this->agentInstruction->updateInstructions($this->proposed_instructions);
            }
        }
        return false;
    }

    public function reject(string $feedback): bool
    {
        if ($this->status === self::STATUS_PENDING) {
            $this->status = self::STATUS_REJECTED;
            $this->feedback = $feedback;
            $this->reviewed_at = now();
            return $this->save();
        }
        return false;
    }

    // Accessors
    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getIsRejectedAttribute(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function getIsReviewedAttribute(): bool
    {
        return !is_null($this->reviewed_at);
    }

    public function getDaysFromSubmissionAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    public function getReviewTimeAttribute(): ?string
    {
        return $this->reviewed_at ? $this->reviewed_at->diffForHumans($this->created_at) : null;
    }
}
