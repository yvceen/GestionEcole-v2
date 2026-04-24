<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToSchool;

class SupportPlan extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_id',
        'classroom_id',
        'reason',
        'actions',
        'start_date',
        'end_date',
        'is_active',
        'created_by_user_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_active'  => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(\App\Models\Student::class, 'student_id');
    }

    public function classroom()
    {
        return $this->belongsTo(\App\Models\Classroom::class, 'classroom_id');
    }

    // ✅ مفيد باش المدير/الإداري يعرف شكون اللي دار Plan
    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_user_id');
    }
}