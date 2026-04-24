<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'teacher_subjects', 'subject_id', 'teacher_id')
            ->withPivot(['school_id', 'assigned_by_user_id'])
            ->withTimestamps();
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'subject_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'subject_id');
    }

    public function pedagogicalResources(): HasMany
    {
        return $this->hasMany(TeacherPedagogicalResource::class, 'subject_id');
    }
}
