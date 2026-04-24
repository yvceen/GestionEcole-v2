<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class HomeworkUserView extends Model
{
    use BelongsToSchool;

    protected $table = 'homework_user_views';

    protected $fillable = [
        'school_id',
        'user_id',
        'homework_id',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function homework()
    {
        return $this->belongsTo(Homework::class, 'homework_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
