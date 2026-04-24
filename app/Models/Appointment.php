<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'parent_user_id',
        'school_id',
        'parent_id',
        'student_id',
        'parent_name',
        'parent_phone',
        'parent_email',
        'title',
        'message',
        'scheduled_at',
        'status',
        'admin_notes',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function getNormalizedStatusAttribute(): string
    {
        $value = strtolower(trim((string) ($this->attributes['status'] ?? '')));

        return match ($value) {
            '', 'draft', 'pending' => 'pending',
            'confirmed', 'approved' => 'approved',
            'completed' => 'completed',
            'archived', 'cancelled', 'rejected' => 'rejected',
            default => 'pending',
        };
    }

    public function setStatusAttribute(?string $value): void
    {
        $normalized = strtolower(trim((string) $value));

        $this->attributes['status'] = match ($normalized) {
            '', 'draft', 'pending' => 'pending',
            'confirmed', 'approved' => 'approved',
            'completed' => 'completed',
            'archived', 'cancelled', 'rejected' => 'rejected',
            default => 'pending',
        };
    }

    public function getScheduledForAttribute(): ?Carbon
    {
        if (!empty($this->scheduled_at)) {
            return $this->scheduled_at instanceof Carbon
                ? $this->scheduled_at
                : Carbon::parse($this->scheduled_at);
        }

        $legacyDate = $this->attributes['date'] ?? null;
        if (empty($legacyDate)) {
            return null;
        }

        try {
            return Carbon::parse($legacyDate);
        } catch (\Throwable) {
            return null;
        }
    }
}
