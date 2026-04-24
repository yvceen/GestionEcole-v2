<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToSchool;

class MonthlyPedagogicReport extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'classroom_id',
        'year',
        'month',
        'summary',
        'recommendations',
        'created_by_user_id',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }
}
