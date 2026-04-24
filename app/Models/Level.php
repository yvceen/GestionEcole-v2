<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToSchool;

class Level extends Model
{
    use BelongsToSchool;
    protected $fillable = ['school_id','code','name','sort_order','is_active'];

    public function classrooms()
    {
        return $this->hasMany(Classroom::class);
    }
}
