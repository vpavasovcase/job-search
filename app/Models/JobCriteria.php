<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobCriteria extends Model
{
    use HasFactory;

    protected $table = 'job_criteria';

    protected $fillable = [
        'user_id',
        'title',
        'keywords',
        'location',
        'min_salary',
        'job_type',
        'required_skills',
        'preferred_skills',
        'additional_requirements',
        'is_active',
    ];

    protected $casts = [
        'keywords' => 'array',
        'required_skills' => 'array',
        'preferred_skills' => 'array',
        'min_salary' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
