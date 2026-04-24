<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToSchool;

class ClassroomFee extends Model
{
    use BelongsToSchool;
    protected $fillable = ['classroom_id','tuition_monthly','transport_monthly','canteen_monthly','insurance_yearly','is_active'];

}
