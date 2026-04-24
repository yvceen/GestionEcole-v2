<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeworkFile extends Model
{
    protected $fillable = [
        'homework_id','path','original_name','mime','size',
    ];

    public function homework()
    {
        return $this->belongsTo(Homework::class);
    }
}
