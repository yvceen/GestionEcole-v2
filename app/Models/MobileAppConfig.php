<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileAppConfig extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'platform',
        'latest_version',
        'minimum_supported_version',
        'update_message',
        'update_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
