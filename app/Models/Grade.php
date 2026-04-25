<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'student_id',
        'classroom_id',
        'teacher_id',
        'subject_id',
        'assessment_id',
        'score',
        'max_score',
        'comment',
    ];

    protected $casts = [
        'score'     => 'decimal:2',
        'max_score' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Student::class, 'student_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(\App\Models\AcademicYear::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Classroom::class, 'classroom_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Subject::class, 'subject_id');
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Assessment::class, 'assessment_id');
    }
}
