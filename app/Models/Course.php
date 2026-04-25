<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use BelongsToSchool;

    protected $table = 'courses';

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'classroom_id',
        'teacher_id',
        'created_by_user_id',
        'title',
        'description',
        'published_at',
        'status',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'file',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function attachments()
    {
        return $this->hasMany(CourseAttachment::class, 'course_id');
    }
}
