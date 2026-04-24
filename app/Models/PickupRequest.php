<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupRequest extends Model
{
    use BelongsToSchool;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'school_id',
        'student_id',
        'parent_user_id',
        'requested_pickup_at',
        'reason',
        'status',
        'reviewed_by_user_id',
        'reviewed_at',
        'completed_at',
        'decision_note',
    ];

    protected $casts = [
        'requested_pickup_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_COMPLETED,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
