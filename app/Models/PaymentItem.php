<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToSchool;

class PaymentItem extends Model
{
    use BelongsToSchool;
    protected $fillable = ['payment_id','student_id','label','amount','period_month'];
}
