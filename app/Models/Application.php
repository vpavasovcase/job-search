<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_id',
        'resume_id',
        'cover_letter',
        'status',
        'submitted_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'submitted_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_WITHDRAWN = 'withdrawn';

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }

    public function communications()
    {
        return $this->hasMany(Communication::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [self::STATUS_REJECTED, self::STATUS_WITHDRAWN]);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->whereNotNull('submitted_at');
    }

    public function scopeNotSubmitted(Builder $query): Builder
    {
        return $query->whereNull('submitted_at');
    }

    public function scopeRecentlySubmitted(Builder $query, int $days = 7): Builder
    {
        return $query->where('submitted_at', '>=', now()->subDays($days));
    }

    // Methods
    public function submit(): bool
    {
        if ($this->status === self::STATUS_DRAFT) {
            $this->status = self::STATUS_SUBMITTED;
            $this->submitted_at = now();
            return $this->save();
        }
        return false;
    }

    public function withdraw(): bool
    {
        if ($this->status !== self::STATUS_WITHDRAWN) {
            $this->status = self::STATUS_WITHDRAWN;
            return $this->save();
        }
        return false;
    }

    public function updateStatus(string $status): bool
    {
        if (in_array($status, [
            self::STATUS_DRAFT,
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_REJECTED,
            self::STATUS_ACCEPTED,
            self::STATUS_WITHDRAWN
        ])) {
            $this->status = $status;
            return $this->save();
        }
        return false;
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return !in_array($this->status, [self::STATUS_REJECTED, self::STATUS_WITHDRAWN]);
    }

    public function getIsSubmittedAttribute(): bool
    {
        return !is_null($this->submitted_at);
    }

    public function getDaysFromSubmissionAttribute(): ?int
    {
        return $this->submitted_at ? $this->submitted_at->diffInDays(now()) : null;
    }
}
