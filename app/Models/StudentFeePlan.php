<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToSchool;

class StudentFeePlan extends Model
{
    use BelongsToSchool;
    protected $fillable = [
        'school_id',
        'student_id',
        'tuition_monthly',
        'transport_monthly',
        'canteen_monthly',
        'insurance_yearly',
        'insurance_paid',
        'starts_month',
        'notes',
    ];

    protected $casts = [
        'insurance_paid' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
