<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Interview extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_id',
        'type',
        'scheduled_at',
        'duration_minutes',
        'location',
        'notes',
        'status',
        'metadata',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'duration_minutes' => 'integer',
        'metadata' => 'array',
    ];

    // Type constants
    const TYPE_PHONE = 'phone';
    const TYPE_VIDEO = 'video';
    const TYPE_ONSITE = 'onsite';
    const TYPE_TECHNICAL = 'technical';
    const TYPE_BEHAVIORAL = 'behavioral';

    // Status constants
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_RESCHEDULED = 'rescheduled';
    const STATUS_NO_SHOW = 'no_show';

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    // Scopes
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('scheduled_at', '>', now())
                    ->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_COMPLETED]);
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('scheduled_at', '<', now());
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('scheduled_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            self::STATUS_CANCELLED,
            self::STATUS_COMPLETED,
            self::STATUS_NO_SHOW
        ]);
    }

    // Methods
    public function confirm(): bool
    {
        if ($this->status === self::STATUS_SCHEDULED) {
            $this->status = self::STATUS_CONFIRMED;
            return $this->save();
        }
        return false;
    }

    public function complete(): bool
    {
        if (in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_CONFIRMED])) {
            $this->status = self::STATUS_COMPLETED;
            return $this->save();
        }
        return false;
    }

    public function cancel(): bool
    {
        if (!in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_COMPLETED])) {
            $this->status = self::STATUS_CANCELLED;
            return $this->save();
        }
        return false;
    }

    public function reschedule(string $newDateTime, ?int $newDuration = null): bool
    {
        if (!in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_COMPLETED])) {
            $this->scheduled_at = $newDateTime;
            if ($newDuration) {
                $this->duration_minutes = $newDuration;
            }
            $this->status = self::STATUS_RESCHEDULED;
            return $this->save();
        }
        return false;
    }

    // Accessors
    public function getIsUpcomingAttribute(): bool
    {
        return $this->scheduled_at->isFuture() && 
               !in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_COMPLETED]);
    }

    public function getIsPastAttribute(): bool
    {
        return $this->scheduled_at->isPast();
    }

    public function getIsActiveAttribute(): bool
    {
        return !in_array($this->status, [
            self::STATUS_CANCELLED,
            self::STATUS_COMPLETED,
            self::STATUS_NO_SHOW
        ]);
    }

    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        if ($hours > 0) {
            return $hours . 'h ' . ($minutes > 0 ? $minutes . 'm' : '');
        }
        
        return $minutes . 'm';
    }

    public function getEndTimeAttribute(): ?\DateTime
    {
        return $this->scheduled_at ? $this->scheduled_at->addMinutes($this->duration_minutes) : null;
    }
}
