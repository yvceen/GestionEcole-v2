<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeworkSubmissionFile extends Model
{
    protected $fillable = [
        'submission_id','path','original_name','mime','size',
    ];

    public function submission()
    {
        return $this->belongsTo(HomeworkSubmission::class, 'submission_id');
    }
}
