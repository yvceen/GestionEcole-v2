<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'full_name',
        'birth_date',
        'gender',
        'parent_user_id',
        'user_id',
        'card_token',
        'classroom_id',
        'archived_at',
        'archived_by_user_id',
        'archive_reason',
    ];

    // (اختياري) باش birth_date يتقرأ كـ date ف Laravel
    protected $casts = [
        'birth_date' => 'date',
        'archived_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function getIsArchivedAttribute(): bool
    {
        return $this->archived_at !== null;
    }

    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function studentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Classroom::class);
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'archived_by_user_id');
    }

    public function feePlan(): HasOne
    {
        return $this->hasOne(\App\Models\StudentFeePlan::class);
    }

    public function transportAssignment(): HasOne
    {
        return $this->hasOne(\App\Models\TransportAssignment::class)
            ->where('is_active', true);
    }

    public function parentFee(): HasOne
    {
        return $this->hasOne(\App\Models\ParentStudentFee::class, 'student_id');
    }

    public function notes(): HasMany
    {
        // ملاحظة: أنت كتستعمل student_id ف StudentNote (وهذا اللي خليت)
        return $this->hasMany(\App\Models\StudentNote::class, 'student_id');
    }

    // ✅ مهم باش fiche ديال directeur تخدم: $student->load('grades...')
    public function grades(): HasMany
    {
        return $this->hasMany(\App\Models\Grade::class, 'student_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(\App\Models\Attendance::class, 'student_id');
    }

    public function activityParticipants(): HasMany
    {
        return $this->hasMany(\App\Models\ActivityParticipant::class, 'student_id');
    }

    public function behaviors(): HasMany
    {
        return $this->hasMany(\App\Models\StudentBehavior::class, 'student_id');
    }

    public function transportLogs(): HasMany
    {
        return $this->hasMany(\App\Models\TransportLog::class, 'student_id');
    }

    public function supportPlans(): HasMany
    {
        return $this->hasMany(\App\Models\SupportPlan::class, 'student_id');
    }
}
