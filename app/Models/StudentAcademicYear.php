<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAcademicYear extends Model
{
    use BelongsToSchool;

    public const STATUS_ENROLLED = 'enrolled';
    public const STATUS_PROMOTED = 'promoted';
    public const STATUS_REPEATED = 'repeated';
    public const STATUS_TRANSFERRED = 'transferred';
    public const STATUS_LEFT = 'left';

    protected $fillable = [
        'school_id',
        'student_id',
        'academic_year_id',
        'classroom_id',
        'status',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_ENROLLED,
            self::STATUS_PROMOTED,
            self::STATUS_REPEATED,
            self::STATUS_TRANSFERRED,
            self::STATUS_LEFT,
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
