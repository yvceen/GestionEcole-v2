<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class HomeworkAttachment extends Model
{
    use BelongsToSchool;

    protected $table = 'homework_attachments';

    protected $fillable = [
        'school_id',
        'homework_id',
        'original_name',
        'path',
        'mime',
        'size',
    ];

    public function homework()
    {
        return $this->belongsTo(Homework::class, 'homework_id');
    }
}
