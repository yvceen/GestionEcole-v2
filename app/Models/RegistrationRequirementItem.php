<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class RegistrationRequirementItem extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'category',
        'label',
        'notes',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];
}
