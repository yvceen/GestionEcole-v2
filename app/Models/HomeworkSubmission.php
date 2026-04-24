<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeworkSubmission extends Model
{
    protected $fillable = [
        'homework_id','student_id','parent_user_id','description','submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function homework()
    {
        return $this->belongsTo(Homework::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function files()
    {
        return $this->hasMany(HomeworkSubmissionFile::class, 'submission_id');
    }
}
