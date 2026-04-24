<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    use HasFactory, BelongsToSchool;

    protected $table = 'assessments';

    protected $fillable = [
        'school_id',
        'teacher_id',
        'classroom_id',
        'subject_id',
        'title',
        'type',
        'date',
        'coefficient',
        'max_score',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
        'coefficient' => 'float',
        'max_score' => 'integer',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'assessment_id');
    }

    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeForSchool($query, ?int $schoolId)
    {
        return $schoolId ? $query->where('school_id', $schoolId) : $query;
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderByDesc('date')->orderByDesc('id');
    }
}
