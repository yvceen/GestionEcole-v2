<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class CourseAttachment extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'course_id',
        'original_name',
        'path',
        'mime',
        'size',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
