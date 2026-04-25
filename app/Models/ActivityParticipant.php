<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityParticipant extends Model
{
    use BelongsToSchool;

    public const CONFIRMATION_PENDING = 'pending';
    public const CONFIRMATION_CONFIRMED = 'confirmed';
    public const CONFIRMATION_DECLINED = 'declined';
    public const ATTENDANCE_PRESENT = 'present';
    public const ATTENDANCE_ABSENT = 'absent';

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'activity_id',
        'student_id',
        'confirmation_status',
        'confirmed_at',
        'attendance_status',
        'attended_at',
        'note',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'attended_at' => 'datetime',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
