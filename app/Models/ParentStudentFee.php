<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToSchool;

class ParentStudentFee extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'parent_user_id',
        'student_id',
        'tuition_monthly',
        'transport_monthly',
        'canteen_monthly',
        'insurance_yearly',
        'starts_month',
        'notes',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->school_id) && app()->bound('current_school_id')) {
                $model->school_id = app('current_school_id');
            }
        });
    }

    public function parentUser()
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
