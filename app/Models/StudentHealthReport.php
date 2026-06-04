<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentHealthReport extends Model
{
    use BelongsToSchool;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'school_id', 'student_id', 'reported_by_user_id', 'source', 'type', 'severity',
        'condition_name', 'symptoms', 'instructions', 'starts_at', 'expected_return_at',
        'status', 'visible_to_teacher', 'visible_to_driver', 'resolved_at', 'resolved_by_user_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expected_return_at' => 'date',
        'visible_to_teacher' => 'boolean',
        'visible_to_driver' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}
