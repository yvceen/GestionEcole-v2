<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use BelongsToSchool;

    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_LATE = 'late';
    public const RECORDED_VIA_TEACHER = 'teacher_register';
    public const RECORDED_VIA_QR = 'qr_scan';
    public const RECORDED_VIA_MANUAL = 'manual_review';
    public const RECORDED_VIA_AUTO_ABSENT = 'system_auto_absent';

    protected $fillable = [
        'school_id',
        'student_id',
        'classroom_id',
        'date',
        'status',
        'check_in_at',
        'check_out_at',
        'note',
        'marked_by_user_id',
        'scanned_by_user_id',
        'recorded_via',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PRESENT,
            self::STATUS_ABSENT,
            self::STATUS_LATE,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Student::class, 'student_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Classroom::class, 'classroom_id');
    }

    // ✅ اللي علّم الغياب (teacher/admin/director)
    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'marked_by_user_id');
    }

    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'scanned_by_user_id');
    }
}
