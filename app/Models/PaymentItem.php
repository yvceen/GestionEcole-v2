<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToSchool;

class PaymentItem extends Model
{
    use BelongsToSchool;
    protected $fillable = ['school_id', 'academic_year_id', 'payment_id','student_id','label','amount','period_month'];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
