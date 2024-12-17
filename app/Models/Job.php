<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Job extends Model
{
    use HasFactory;

    protected $table = 'job_listings';

    protected $fillable = [
        'user_id',
        'title',
        'company',
        'location',
        'description',
        'job_link',
        'salary_min',
        'salary_max',
        'job_type',
        'required_skills',
        'preferred_skills',
        'status',
    ];

    protected $casts = [
        'required_skills' => 'array',
        'preferred_skills' => 'array',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
    ];

    // Status constants
    const STATUS_NEW = 'new';
    const STATUS_APPLIED = 'applied';
    const STATUS_INTERVIEWING = 'interviewing';
    const STATUS_OFFERED = 'offered';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DECLINED = 'declined';

    // Job type constants
    const TYPE_FULL_TIME = 'full-time';
    const TYPE_PART_TIME = 'part-time';
    const TYPE_CONTRACT = 'contract';
    const TYPE_FREELANCE = 'freelance';
    const TYPE_INTERNSHIP = 'internship';

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function communications()
    {
        return $this->hasMany(Communication::class);
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [self::STATUS_REJECTED, self::STATUS_DECLINED]);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByJobType(Builder $query, string $type): Builder
    {
        return $query->where('job_type', $type);
    }

    public function scopeWithinSalaryRange(Builder $query, float $min, ?float $max = null): Builder
    {
        $query->where(function ($q) use ($min, $max) {
            $q->where('salary_min', '>=', $min)
              ->orWhere('salary_max', '>=', $min);
        });

        if ($max) {
            $query->where(function ($q) use ($max) {
                $q->where('salary_min', '<=', $max)
                  ->orWhere('salary_max', '<=', $max);
            });
        }

        return $query;
    }

    public function scopeMatchingSkills(Builder $query, array $skills): Builder
    {
        return $query->where(function ($q) use ($skills) {
            foreach ($skills as $skill) {
                $q->orWhereJsonContains('required_skills', $skill)
                  ->orWhereJsonContains('preferred_skills', $skill);
            }
        });
    }

    // Accessors
    public function getSalaryRangeAttribute(): string
    {
        if (!$this->salary_min && !$this->salary_max) {
            return 'Not specified';
        }

        if (!$this->salary_max) {
            return '$' . number_format($this->salary_min) . '+';
        }

        if ($this->salary_min == $this->salary_max) {
            return '$' . number_format($this->salary_min);
        }

        return '$' . number_format($this->salary_min) . ' - $' . number_format($this->salary_max);
    }

    public function getSkillsAttribute(): array
    {
        return array_unique(array_merge(
            $this->required_skills ?? [],
            $this->preferred_skills ?? []
        ));
    }

    public function getIsActiveAttribute(): bool
    {
        return !in_array($this->status, [self::STATUS_REJECTED, self::STATUS_DECLINED]);
    }
}
