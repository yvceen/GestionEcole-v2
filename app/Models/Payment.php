<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToSchool;

class Payment extends Model
{
    use BelongsToSchool;
    
    protected $fillable = [
        'school_id',
        'academic_year_id',
        'student_id',
        'receipt_id',
        'amount',
        'method',
        'period_month',
        'paid_at',
        'received_by_admin_user_id',
        'note',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'period_month' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'received_by_admin_user_id');
    }
}
