<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToSchool;

class Level extends Model
{
    use BelongsToSchool;
    protected $fillable = ['school_id','education_cycle_id','code','name','sort_order','is_active'];

    public function cycle()
    {
        return $this->belongsTo(EducationCycle::class, 'education_cycle_id');
    }

    public function classrooms()
    {
        return $this->hasMany(Classroom::class);
    }
}
