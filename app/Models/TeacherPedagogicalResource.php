<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TeacherPedagogicalResource extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'teacher_id',
        'subject_id',
        'classroom_id',
        'title',
        'description',
        'file_path',
        'mime_type',
        'size_bytes',
        'is_active',
        'created_by_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'size_bytes' => 'integer',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getFileUrlAttribute(): ?string
    {
        $path = trim((string) ($this->file_path ?? ''));
        if ($path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
