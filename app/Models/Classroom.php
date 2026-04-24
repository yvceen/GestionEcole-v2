<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToSchool;

class Classroom extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id','level_id','section','name','sort_order','is_active'];

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function fee()
    {
        return $this->hasOne(\App\Models\ClassroomFee::class);
    }

    public function timetables()
    {
        return $this->hasMany(\App\Models\Timetable::class);
    }

    // ✅ حيدنا type-hint باش مايبقاش كيتخلط App\Models\BelongsToMany مع Illuminate...
    public function teachers()
    {
        return $this->belongsToMany(\App\Models\User::class, 'classroom_teacher', 'classroom_id', 'teacher_id')
            ->withPivot(['school_id','assigned_by_user_id'])
            ->withTimestamps();
    }
}
