<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseFile extends Model
{
    protected $fillable = [
        'course_id','path','original_name','mime','size',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
