<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Communication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_id',
        'application_id',
        'type',
        'direction',
        'content',
        'status',
        'sent_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
    ];

    // Type constants
    const TYPE_EMAIL = 'email';
    const TYPE_PHONE = 'phone';
    const TYPE_VIDEO = 'video';
    const TYPE_IN_PERSON = 'in_person';
    const TYPE_OTHER = 'other';

    // Direction constants
    const DIRECTION_INCOMING = 'incoming';
    const DIRECTION_OUTGOING = 'outgoing';

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_READ = 'read';
    const STATUS_FAILED = 'failed';

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    // Scopes
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByDirection(Builder $query, string $direction): Builder
    {
        return $query->where('direction', $direction);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeIncoming(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_INCOMING);
    }

    public function scopeOutgoing(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_OUTGOING);
    }

    public function scopeEmails(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_EMAIL);
    }

    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_INCOMING)
                    ->whereNotIn('status', [self::STATUS_READ]);
    }

    // Methods
    public function send(): bool
    {
        if (in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED])) {
            $this->status = self::STATUS_SENT;
            $this->sent_at = now();
            return $this->save();
        }
        return false;
    }

    public function markAsRead(): bool
    {
        if ($this->direction === self::DIRECTION_INCOMING && $this->status !== self::STATUS_READ) {
            $this->status = self::STATUS_READ;
            return $this->save();
        }
        return false;
    }

    public function schedule(string $datetime): bool
    {
        if ($this->status === self::STATUS_DRAFT) {
            $this->status = self::STATUS_SCHEDULED;
            $this->sent_at = $datetime;
            return $this->save();
        }
        return false;
    }

    // Accessors
    public function getIsIncomingAttribute(): bool
    {
        return $this->direction === self::DIRECTION_INCOMING;
    }

    public function getIsOutgoingAttribute(): bool
    {
        return $this->direction === self::DIRECTION_OUTGOING;
    }

    public function getIsReadAttribute(): bool
    {
        return $this->status === self::STATUS_READ;
    }

    public function getIsSentAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_DELIVERED, self::STATUS_READ]);
    }
}
