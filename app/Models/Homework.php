<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\SoftDeletes;

class Homework extends Model
{
    use BelongsToSchool;
    use SoftDeletes;

    protected $table = 'homeworks';

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'classroom_id',
        'teacher_id',
        'subject_id',
        'title',
        'description',
        'due_at',
        'status',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function attachments()
    {
        return $this->hasMany(HomeworkAttachment::class, 'homework_id');
    }

    public function getNormalizedStatusAttribute(): string
    {
        $value = strtolower(trim((string) ($this->attributes['status'] ?? '')));

        return match ($value) {
            '', 'draft', 'pending' => 'pending',
            'confirmed', 'approved' => 'approved',
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
            'archived', 'cancelled', 'rejected' => 'rejected',
            default => 'pending',
        };
    }

    public function scopePending(Builder $query): Builder
    {
        if (!Schema::hasTable('homeworks') || !Schema::hasColumn('homeworks', 'status')) {
            return $query;
        }

        return $query->where(function (Builder $inner) {
            $inner->whereNull('status')
                ->orWhere('status', '')
                ->orWhereIn('status', ['draft', 'pending']);
        });
    }

    public function scopeApproved(Builder $query): Builder
    {
        if (!Schema::hasTable('homeworks') || !Schema::hasColumn('homeworks', 'status')) {
            return $query;
        }

        return $query->whereIn('status', ['approved', 'confirmed']);
    }
}
