<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToSchool;

class ClassCouncil extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'classroom_id',
        'date',
        'title',
        'decisions',
        'created_by_user_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }
}
