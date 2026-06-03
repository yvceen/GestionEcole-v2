<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class EducationCycle extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'color',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function levels()
    {
        return $this->hasMany(Level::class)->orderBy('sort_order')->orderBy('name');
    }
}
